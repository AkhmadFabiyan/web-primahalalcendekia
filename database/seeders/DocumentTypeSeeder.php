<?php

namespace Database\Seeders;

use App\Modules\Documents\Models\DocumentType;
use Illuminate\Database\Seeder;

class DocumentTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            ['code' => 'NIB', 'name' => 'NIB', 'category' => 'ADMINISTRASI', 'is_required' => true],
            ['code' => 'NPWP', 'name' => 'NPWP', 'category' => 'ADMINISTRASI', 'is_required' => true],
            ['code' => 'KTP_PIC', 'name' => 'KTP PIC', 'category' => 'ADMINISTRASI', 'is_required' => true],
            ['code' => 'DAFTAR_PRODUK', 'name' => 'Daftar Produk', 'category' => 'ADMINISTRASI', 'is_required' => true],
            ['code' => 'DAFTAR_BAHAN', 'name' => 'Daftar Bahan', 'category' => 'ADMINISTRASI', 'is_required' => true],
            ['code' => 'MANUAL_SJPH', 'name' => 'Manual SJPH', 'category' => 'ADMINISTRASI', 'is_required' => true],
            ['code' => 'SURAT_PERNYATAAN', 'name' => 'Surat Pernyataan', 'category' => 'ADMINISTRASI', 'is_required' => true],
            ['code' => 'DENAH_LOKASI', 'name' => 'Denah Lokasi', 'category' => 'ADMINISTRASI', 'is_required' => true],
            ['code' => 'FOTO_FASILITAS', 'name' => 'Foto Fasilitas', 'category' => 'ADMINISTRASI', 'is_required' => true],
            ['code' => 'DOKUMEN_LAINNYA', 'name' => 'Dokumen Pendukung Lainnya', 'category' => 'ADMINISTRASI', 'is_required' => false],
        ];

        foreach ($types as $index => $type) {
            DocumentType::updateOrCreate(
                ['code' => $type['code']],
                [
                    'name' => $type['name'],
                    'category' => $type['category'],
                    'is_required' => $type['is_required'],
                    'sort_order' => $index + 1,
                    'is_active' => true,
                ]
            );
        }
    }
}
