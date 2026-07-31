<?php

namespace App\Modules\Leads\Services;

use App\Modules\Leads\Models\Lead;
use App\Modules\Leads\Enums\LeadStatus;
use App\Modules\Clients\Models\Client;
use App\Modules\Clients\Models\Partner;
use App\Modules\Projects\Models\Project;
use App\Modules\Projects\Models\ProjectAssignment;
use App\Modules\Payments\Models\Invoice;
use App\Modules\Projects\Enums\ProjectStatus;
use App\Modules\Projects\Enums\AssignmentRole;
use App\Modules\Payments\Enums\InvoiceType;
use App\Modules\Payments\Enums\InvoiceAudience;
use App\Modules\Payments\Enums\InvoiceStatus;
use App\Modules\Clients\Enums\ClientType;
use App\Modules\Leads\Enums\PaymentScheme;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LeadConversionService
{
    /**
     * Finds existing client candidates based on normalized name.
     */
    public function findClientCandidates(Lead $lead)
    {
        $normalizedName = $this->normalizeName($lead->company_name);
        // Note: exact matching logic can be extended to NIB/NPWP later when those fields are added.
        return Client::whereRaw('LOWER(TRIM(company_name)) = ?', [$normalizedName])->get();
    }

    /**
     * Normalizes a company name for matching.
     */
    private function normalizeName(string $name): string
    {
        $name = strtolower(trim($name));
        $name = preg_replace('/\s+/', ' ', $name);
        // We do not remove PT, CV, etc. as per user requirements
        return $name;
    }

    /**
     * Executes the atomic conversion of a Lead to a Project.
     * 
     * @param Lead $lead The lead to convert
     * @param string|null $forceClientId If provided, will reuse this client instead of creating a new one
     */
    public function convert(Lead $lead, ?string $forceClientId = null): Project
    {
        return DB::transaction(function () use ($lead, $forceClientId) {
            // Lock the lead
            $lead = Lead::where('id', $lead->id)->lockForUpdate()->firstOrFail();

            // Idempotency checks
            if ($lead->status === LeadStatus::DEAL) {
                if ($lead->project) {
                    return $lead->project;
                }
                throw new \Exception('Invariant rusak: Lead sudah DEAL tetapi tidak memiliki Project.');
            }

            if ($lead->status === LeadStatus::CANCELLED) {
                throw new \Exception('Lead sudah dibatalkan dan tidak dapat dikonversi.');
            }

            // 1. Partner
            $partnerId = $lead->partner_id;
            if ($lead->client_type === ClientType::PARTNER && empty($partnerId)) {
                // Determine if we need to create a new partner
                $normalizedPartnerName = $this->normalizeName($lead->partner_name);
                $existingPartner = Partner::whereRaw('LOWER(TRIM(name)) = ?', [$normalizedPartnerName])
                    ->lockForUpdate()
                    ->first();

                if ($existingPartner) {
                    $partnerId = $existingPartner->id;
                } else {
                    $newPartner = Partner::create([
                        'partner_code' => $this->generatePartnerCode(),
                        'name' => $lead->partner_name,
                        'pic_name' => $lead->partner_pic_name,
                        'phone' => $lead->partner_phone,
                        'email' => $lead->partner_email,
                    ]);
                    $partnerId = $newPartner->id;
                }
            }

            // 2. Client
            $clientId = $forceClientId;
            if (!$clientId) {
                $candidates = $this->findClientCandidates($lead);
                if ($candidates->isNotEmpty()) {
                    throw new \Exception('Ditemukan kandidat Klien dengan nama yang sama. Silakan tinjau dan konfirmasi.');
                }
            }

            $client = null;
            if ($clientId) {
                $client = Client::where('id', $clientId)->lockForUpdate()->firstOrFail();
                if ($client->project) {
                    throw new \Exception('Client yang dipilih sudah memiliki Project. Konversi dibatalkan untuk mencegah duplikasi (1 Client = 1 Project).');
                }
            } else {
                $client = Client::create([
                    'business_id' => $this->generateBusinessId(),
                    'client_type' => $lead->client_type,
                    'partner_id' => $partnerId,
                    'company_name' => $lead->company_name,
                    'business_sector' => $lead->business_sector,
                    'address' => $lead->address,
                    'city' => $lead->city,
                    'province' => $lead->province,
                    'pic_name' => $lead->pic_name,
                    'pic_phone' => $lead->pic_phone,
                    'pic_email' => $lead->pic_email,
                ]);
            }

            // 3. Project
            $project = Project::create([
                'source_lead_id' => $lead->id,
                'client_id' => $client->id,
                'project_name' => $lead->company_name,
                'service_type' => $lead->service_type,
                'client_nominal' => $lead->client_nominal,
                'partner_nominal' => $lead->partner_nominal,
                'payment_scheme' => $lead->payment_scheme,
                'installment_count' => $lead->installment_count,
                'status' => ProjectStatus::WAITING_ACTIVATION,
            ]);

            // 4. Assignment Marketing
            $marketingUser = \App\Models\User::find($lead->marketing_id);
            if (!$marketingUser || !$marketingUser->hasRole(\App\Enums\Role::MARKETING->value)) {
                throw new \Exception('User Marketing tidak valid atau tidak memiliki role Marketing.');
            }
            if ($marketingUser->status !== 'ACTIVE') {
                throw new \Exception('User Marketing tidak dalam status aktif.');
            }

            ProjectAssignment::create([
                'project_id' => $project->id,
                'user_id' => $lead->marketing_id,
                'assignment_role' => AssignmentRole::MARKETING,
                'assigned_by' => auth()->id() ?? $lead->marketing_id,
            ]);

            // 5. Invoices (Draft)
            $billingGroupId = (string) Str::uuid();
            $clientAmount = $this->calculateActivationAmount($lead->payment_scheme, $lead->client_nominal, $lead->installment_count);

            // Invoice for Client
            Invoice::create([
                'project_id' => $project->id,
                'invoice_type' => InvoiceType::ACTIVATION,
                'billing_group_id' => $billingGroupId,
                'audience' => InvoiceAudience::CLIENT,
                'partner_id' => null,
                'sequence' => 1,
                'subtotal' => $clientAmount,
                'discount_total' => 0,
                'status' => InvoiceStatus::DRAFT,
                'billing_snapshot' => $this->createBillingSnapshot($client),
            ]);

            // Invoice for Partner if ClientType is PARTNER
            if ($lead->client_type === ClientType::PARTNER) {
                $partnerAmount = $this->calculateActivationAmount($lead->payment_scheme, $lead->partner_nominal, $lead->installment_count);
                
                Invoice::create([
                    'project_id' => $project->id,
                    'invoice_type' => InvoiceType::ACTIVATION,
                    'billing_group_id' => $billingGroupId,
                    'audience' => InvoiceAudience::PARTNER,
                    'partner_id' => $partnerId,
                    'sequence' => 1,
                    'subtotal' => $partnerAmount,
                    'discount_total' => 0,
                    'status' => InvoiceStatus::DRAFT,
                    'billing_snapshot' => $this->createBillingSnapshot($client), // Partner snapshot might also include partner details
                ]);
            }

            // 6. Update Lead
            $lead->status = LeadStatus::DEAL;
            $lead->save();

            // Log activity manually or rely on Spatie
            activity()
                ->performedOn($lead)
                ->event('converted')
                ->log('Lead dikonversi menjadi Project: ' . $client->business_id);

            return $project;
        });
    }

    private function calculateActivationAmount(PaymentScheme $scheme, float $total, int $installments): float
    {
        if ($scheme === PaymentScheme::FULL_PAYMENT) {
            return $total;
        }
        
        // For INSTALLMENT, we assume the first term is an equal division. 
        // We round to 2 decimal places.
        return round($total / max(1, $installments), 2);
    }

    private function createBillingSnapshot(Client $client): array
    {
        return [
            'company_name' => $client->company_name,
            'address' => $client->address,
            'city' => $client->city,
            'province' => $client->province,
            'pic_name' => $client->pic_name,
            'pic_phone' => $client->pic_phone,
            'pic_email' => $client->pic_email,
        ];
    }

    private function generateBusinessId(): string
    {
        $year = date('Y');
        $name = 'CLIENT_' . $year;
        
        $sequence = \DB::table('sequences')
            ->where('name', $name)
            ->lockForUpdate()
            ->first();

        if (!$sequence) {
            \DB::table('sequences')->insert([
                'name' => $name,
                'value' => 1,
            ]);
            $nextValue = 1;
        } else {
            $nextValue = $sequence->value + 1;
            \DB::table('sequences')
                ->where('name', $name)
                ->update(['value' => $nextValue]);
        }

        return sprintf('PHC-HAL-%s-%04d', $year, $nextValue);
    }

    private function generatePartnerCode(): string
    {
        $year = date('Y');
        $name = 'PARTNER_' . $year;
        
        $sequence = \DB::table('sequences')
            ->where('name', $name)
            ->lockForUpdate()
            ->first();

        if (!$sequence) {
            \DB::table('sequences')->insert([
                'name' => $name,
                'value' => 1,
            ]);
            $nextValue = 1;
        } else {
            $nextValue = $sequence->value + 1;
            \DB::table('sequences')
                ->where('name', $name)
                ->update(['value' => $nextValue]);
        }

        return sprintf('PARTNER-%s-%04d', $year, $nextValue);
    }
}
