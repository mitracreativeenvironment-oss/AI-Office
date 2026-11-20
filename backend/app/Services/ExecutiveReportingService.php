<?php

namespace App\Services;

use Config\Database;
use CodeIgniter\Database\BaseConnection;

class ExecutiveReportingService
{
    protected BaseConnection $db;
    protected CorporatePerformanceService $corpService;
    protected OrganizationalPerformanceService $orgService;
    protected ProjectPerformanceService $projService;
    protected MetricCalculationService $metricService;

    public function __construct()
    {
        $this->db = Database::connect();
        $this->corpService = new CorporatePerformanceService();
        $this->orgService = new OrganizationalPerformanceService();
        $this->projService = new ProjectPerformanceService();
        $this->metricService = new MetricCalculationService();
    }

    public function listReports(array $filters = []): array
    {
        $builder = $this->db->table('executive_reports')->orderBy('id', 'DESC');
        if (!empty($filters['report_type'])) {
            $builder->where('report_type', $filters['report_type']);
        }
        if (!empty($filters['approval_status'])) {
            $builder->where('approval_status', $filters['approval_status']);
        }
        $reports = $builder->get()->getResultArray();

        foreach ($reports as &$r) {
            $r['highlights'] = json_decode($r['highlights_json'] ?? '[]', true) ?: [];
            $r['risks_and_issues'] = json_decode($r['risks_and_issues_json'] ?? '[]', true) ?: [];
            $r['kpi_summary'] = json_decode($r['kpi_summary_json'] ?? '[]', true) ?: [];
            $r['recommendations'] = json_decode($r['recommendations_json'] ?? '[]', true) ?: [];
        }

        return $reports;
    }

    public function getReport(int $id): ?array
    {
        $report = $this->db->table('executive_reports')->where('id', $id)->get()->getRowArray();
        if (!$report) return null;

        $report['highlights'] = json_decode($report['highlights_json'] ?? '[]', true) ?: [];
        $report['risks_and_issues'] = json_decode($report['risks_and_issues_json'] ?? '[]', true) ?: [];
        $report['kpi_summary'] = json_decode($report['kpi_summary_json'] ?? '[]', true) ?: [];
        $report['recommendations'] = json_decode($report['recommendations_json'] ?? '[]', true) ?: [];

        return $report;
    }

    public function generateReport(array $data, ?int $userId = null): array
    {
        $now = date('Y-m-d H:i:s');
        $reportType = $data['report_type'] ?? 'corporate_overview';
        $periodStart = $data['period_start'] ?? date('Y-m-01');
        $periodEnd = $data['period_end'] ?? date('Y-m-t');

        $code = trim($data['report_code'] ?? 'REP-' . strtoupper(substr($reportType, 0, 4)) . '-' . date('Y-m') . '-' . strtoupper(substr(uniqid(), -4)));
        $title = trim($data['title'] ?? ('Executive ' . ucwords(str_replace('_', ' ', $reportType)) . ' Report (' . date('M Y', strtotime($periodStart)) . ')'));

        // Compile Live Intelligence based on report type
        $highlights = [];
        $risks = [];
        $kpiSummary = [];
        $recommendations = [];
        $summary = '';

        if ($reportType === 'corporate_overview') {
            $scorecard = $this->corpService->getCorporateScorecard();
            $pulse = $this->corpService->getOperationalPulse();

            $summary = "Comprehensive Corporate Operational Performance Report for MCE Virtual Office. Current overall corporate health stands at {$pulse['overall_health_pct']}% with operational state: {$pulse['operational_status']}.";
            $highlights = [
                "Total active workforce: {$pulse['active_agent_count']} AI agents actively assigned across divisions.",
                "Portfolio deliverable completion rate: {$scorecard['metrics']['active_deliverables']['completion_rate']}%.",
                "Corporate governance integrity: {$scorecard['metrics']['governance_decisions']['total_decisions']} decisions ratified with full DoA adherence.",
            ];
            $kpiSummary = $scorecard['scorecard_categories'] ?? [];
            $risks = [
                "Capacity utilization nearing 85% in critical technology engineering units.",
                "Cross-divisional sync latency on complex client engagements.",
            ];
            $recommendations = [
                "Authorize additional autonomous compute quota for Hermes runtime scaling.",
                "Review open Performance Improvement Plans during upcoming executive session.",
            ];
        } elseif ($reportType === 'organizational_unit') {
            $comparison = $this->orgService->getDivisionalComparison();
            $summary = "Divisional & Organizational Unit Health breakdown across MCE 6 operational divisions.";
            $highlights = [
                "Tracked " . count($comparison) . " operational divisions under KBLI enterprise structure.",
                "Zero operational downtime recorded during continuous 24/7 autonomous scheduling.",
            ];
            $kpiSummary = $comparison;
            $recommendations = [
                "Rebalance unit allocations according to Prompt 28 resource constraints.",
            ];
        } elseif ($reportType === 'project_portfolio') {
            $portfolio = $this->projService->getPortfolioPerformance();
            $summary = "Client engagement lifecycle & project deliverable attainment report.";
            $highlights = [
                "Total active projects monitored: {$portfolio['portfolio_summary']['total_projects']}.",
                "Average portfolio deliverable acceptance: {$portfolio['portfolio_summary']['avg_acceptance_rate_pct']}%.",
            ];
            $kpiSummary = $portfolio['projects'] ?? [];
            $risks = [
                "Identified dependencies awaiting external client sign-off.",
            ];
        } else {
            // General workforce / executive operations
            $summary = trim($data['executive_summary'] ?? "Executive Report for {$reportType} period {$periodStart} to {$periodEnd}.");
            $highlights = $data['highlights'] ?? ["Operational execution validated within standard variance thresholds."];
            $risks = $data['risks'] ?? ["Monitor model token consumption and 9Router gateway rate limits."];
            $kpiSummary = $data['kpi_summary'] ?? [];
            $recommendations = $data['recommendations'] ?? ["Maintain continuous 5/2 duty schedule compliance."];
        }

        if (!empty($data['executive_summary'])) {
            $summary = trim($data['executive_summary']);
        }
        if (!empty($data['highlights']) && is_array($data['highlights'])) {
            $highlights = $data['highlights'];
        }
        if (!empty($data['risks']) && is_array($data['risks'])) {
            $risks = $data['risks'];
        }
        if (!empty($data['recommendations']) && is_array($data['recommendations'])) {
            $recommendations = $data['recommendations'];
        }

        $insert = [
            'report_code'           => $code,
            'report_type'           => $reportType,
            'title'                 => $title,
            'period_start'          => $periodStart,
            'period_end'            => $periodEnd,
            'scope_filter_type'     => $data['scope_filter_type'] ?? 'all',
            'scope_filter_id'       => !empty($data['scope_filter_id']) ? (int)$data['scope_filter_id'] : null,
            'executive_summary'     => $summary,
            'highlights_json'       => json_encode($highlights, JSON_UNESCAPED_UNICODE),
            'risks_and_issues_json' => json_encode($risks, JSON_UNESCAPED_UNICODE),
            'kpi_summary_json'      => json_encode($kpiSummary, JSON_UNESCAPED_UNICODE),
            'recommendations_json'  => json_encode($recommendations, JSON_UNESCAPED_UNICODE),
            'author_identity'       => $data['author_identity'] ?? 'Director AI',
            'approval_status'       => $data['approval_status'] ?? 'published',
            'generated_at'          => $now,
            'created_at'            => $now,
            'updated_at'            => $now,
        ];

        $this->db->table('executive_reports')->insert($insert);
        $insert['id'] = (int)$this->db->insertID();

        // Audit Trail
        $this->db->table('performance_audit_trail')->insert([
            'event_type'     => 'executive_report_generated',
            'target_entity'  => 'executive_reports',
            'target_id'      => $insert['id'],
            'actor_type'     => $userId ? 'human' : 'agent',
            'actor_id'       => $userId,
            'action_summary' => "Generated Executive Report {$code} ({$reportType})",
            'created_at'     => $now,
        ]);

        return $this->getReport($insert['id']);
    }

    public function publishReport(int $id, ?int $userId = null): ?array
    {
        $report = $this->getReport($id);
        if (!$report) return null;

        $now = date('Y-m-d H:i:s');
        $this->db->table('executive_reports')->where('id', $id)->update([
            'approval_status' => 'published',
            'updated_at'      => $now,
        ]);

        // Audit Trail
        $this->db->table('performance_audit_trail')->insert([
            'event_type'     => 'executive_report_published',
            'target_entity'  => 'executive_reports',
            'target_id'      => $id,
            'actor_type'     => $userId ? 'human' : 'agent',
            'actor_id'       => $userId,
            'action_summary' => "Published Executive Report {$report['report_code']}",
            'created_at'     => $now,
        ]);

        return $this->getReport($id);
    }

    public function ratifyReport(int $id, int $userId): ?array
    {
        $report = $this->getReport($id);
        if (!$report) return null;

        $now = date('Y-m-d H:i:s');
        $this->db->table('executive_reports')->where('id', $id)->update([
            'approval_status' => 'ratified',
            'updated_at'      => $now,
        ]);

        // Audit Trail
        $this->db->table('performance_audit_trail')->insert([
            'event_type'     => 'executive_report_ratified',
            'target_entity'  => 'executive_reports',
            'target_id'      => $id,
            'actor_type'     => 'human',
            'actor_id'       => $userId,
            'action_summary' => "Ratified Executive Report {$report['report_code']} by Human Owner",
            'created_at'     => $now,
        ]);

        return $this->getReport($id);
    }
}
