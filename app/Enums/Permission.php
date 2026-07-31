<?php

namespace App\Enums;

enum Permission: string
{
    case ViewDashboard = 'dashboard.view';

    case ViewLeads = 'leads.view';
    case CreateLeads = 'leads.create';
    case UpdateLeads = 'leads.update';
    case ChangeStatusLeads = 'leads.change_status';

    case ViewClients = 'clients.view';
    case UpdateClients = 'clients.update';
    case CreateLoginAccountClients = 'clients.create_login_account';

    case ViewTasks = 'tasks.view';
    case UpdateTasks = 'tasks.update';

    case UpdateAssignedEntry = 'progress.entry.update_assigned';
    case ReviewEntry = 'progress.entry.review';
    case UpdateAssignedCompanion = 'progress.companion.update_assigned';
    case UpdateAssignedAuditor = 'progress.auditor.update_assigned';
    case UpdatePostAudit = 'progress.auditor.update_post_audit';
    case OverrideProgress = 'progress.override';

    case ViewInvoices = 'invoices.view';
    case PublishInvoices = 'invoices.publish';

    case ViewPayments = 'payments.view';
    case VerifyPayments = 'payments.verify';

    case ViewCertificates = 'certificates.view';
    case UploadCertificates = 'certificates.upload';

    case ViewDocuments = 'documents.view';
    case UploadDocuments = 'documents.upload';
    case UpdateDocuments = 'documents.update';

    case ViewUsers = 'users.view';
    case ManageUsers = 'users.manage';

    case ViewSettings = 'settings.view';
    case ManageSettings = 'settings.manage';

    case ViewReports = 'reports.view';
}
