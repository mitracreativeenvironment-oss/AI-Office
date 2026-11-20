<?php

namespace App\Controllers\Api;

class CompanyController extends BaseApiController
{
    public function profile()
    {
        $db = \Config\Database::connect();
        $company = $db->table('companies')->where('slug', 'mce')->get()->getRowArray();

        if (!$company) {
            return $this->respondNotFound('Company profile not found');
        }

        $divisionsCount = $db->table('divisions')->where('is_active', 1)->countAllResults();
        $agentsCount = $db->table('agents')->where('is_online', 1)->countAllResults();

        $company['metrics'] = [
            'active_divisions' => $divisionsCount,
            'active_agents'    => $agentsCount,
        ];

        return $this->respondSuccess($company, 'Company profile retrieved successfully');
    }

    public function divisions()
    {
        $db = \Config\Database::connect();
        $divisions = $db->table('divisions')->where('is_active', 1)->get()->getResultArray();

        return $this->respondSuccess($divisions, 'Divisions retrieved successfully');
    }
}
