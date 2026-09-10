<?php
declare(strict_types=1);

namespace App\Models;

class PermissionsReference
{
    const string readQuestion = 'view_enquiries_ph';
    const string readAnswer = 'view_enquiries_ph';

    const string askQuestion = 'create_enquiry_ph';
    const string answerQuestion = 'create_quote_ph';

    const string manageUsers = 'manage_users';
    const string manageRoles = 'manage_roles';

    const string manageProducts = 'manage_products';
    const string manageBranches = 'manage_branches';

    const string readContractor = 'view_enquiries_sf';
}
