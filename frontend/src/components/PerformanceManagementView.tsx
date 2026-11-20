import React, { useState, useEffect, useCallback } from 'react';
import { performanceService } from '../services/performanceService';
import type {
  KpiCategory,
  KpiDefinition,
  CorporateScorecard,
  OperationalPulse,
  ExecutiveOverview,
  DivisionalPerformance,
  ProjectPortfolioPerformance,
  EvaluationCycle,
  AgentPerformanceEvaluation,
  CoreValuesAssessment,
  CoreValuesSummary,
  PerformanceImprovementPlan,
  WelfareBonusReference,
  ExecutiveReport,
  ScoreBand,
} from '../types/performance';
import type { User } from '../types/auth';

interface PerformanceManagementViewProps {
  currentUser: User | null;
}

export const PerformanceManagementView: React.FC<PerformanceManagementViewProps> = ({ currentUser }) => {
  const [activeTab, setActiveTab] = useState<
    'scorecard' | 'catalog' | 'executive' | 'divisions' | 'evaluations' | 'core_values' | 'cycles' | 'pips' | 'governance'
  >('scorecard');

  const [loading, setLoading] = useState<boolean>(true);
  const [errorMsg, setErrorMsg] = useState<string | null>(null);
  const [successMsg, setSuccessMsg] = useState<string | null>(null);

  // Core Data States
  const [scorecard, setScorecard] = useState<CorporateScorecard | null>(null);
  const [pulse, setPulse] = useState<OperationalPulse | null>(null);
  const [categories, setCategories] = useState<KpiCategory[]>([]);
  const [kpis, setKpis] = useState<KpiDefinition[]>([]);
  const [execOverview, setExecOverview] = useState<ExecutiveOverview | null>(null);
  const [divisions, setDivisions] = useState<DivisionalPerformance[]>([]);
  const [portfolio, setPortfolio] = useState<ProjectPortfolioPerformance | null>(null);
  const [evaluations, setEvaluations] = useState<AgentPerformanceEvaluation[]>([]);
  const [coreValuesSummary, setCoreValuesSummary] = useState<CoreValuesSummary | null>(null);
  const [agentCoreValues, setAgentCoreValues] = useState<CoreValuesAssessment[]>([]);
  const [cycles, setCycles] = useState<EvaluationCycle[]>([]);
  const [pips, setPips] = useState<PerformanceImprovementPlan[]>([]);
  const [bonuses, setBonuses] = useState<WelfareBonusReference[]>([]);
  const [reports, setReports] = useState<ExecutiveReport[]>([]);

  // Filter & Selection States
  const [selectedKpiCategory, setSelectedKpiCategory] = useState<string>('all');
  const [selectedScoreBand, setSelectedScoreBand] = useState<string>('all');
  const [selectedAgentId, setSelectedAgentId] = useState<number>(5);
  const [selectedReport, setSelectedReport] = useState<ExecutiveReport | null>(null);

  // Modals
  const [showKpiModal, setShowKpiModal] = useState<boolean>(false);
  const [showPipModal, setShowPipModal] = useState<boolean>(false);
  const [showBonusModal, setShowBonusModal] = useState<boolean>(false);
  const [showReportModal, setShowReportModal] = useState<boolean>(false);
  const [showCoreValueModal, setShowCoreValueModal] = useState<boolean>(false);

  // Form states
  const [newKpiCode, setNewKpiCode] = useState<string>('');
  const [newKpiName, setNewKpiName] = useState<string>('');
  const [newKpiCategory, setNewKpiCategory] = useState<number>(1);
  const [newKpiLevel, setNewKpiLevel] = useState<'corporate' | 'executive' | 'organizational' | 'workforce'>('workforce');
  const [newKpiTarget, setNewKpiTarget] = useState<number>(85);
  const [newKpiFormula, setNewKpiFormula] = useState<string>('(completed / total) * 100');

  const [newPipTargetId, setNewPipTargetId] = useState<number>(5);
  const [newPipObjective, setNewPipObjective] = useState<string>('');
  const [newPipGap, setNewPipGap] = useState<string>('');
  const [newPipAction, setNewPipAction] = useState<string>('');

  const [newBonusAgentId, setNewBonusAgentId] = useState<number>(5);
  const [newBonusAmountIdr, setNewBonusAmountIdr] = useState<number>(2500000);
  const [newBonusTickets, setNewBonusTickets] = useState<number>(10);
  const [newBonusJustification, setNewBonusJustification] = useState<string>('');

  const [newReportType, setNewReportType] = useState<string>('corporate_overview');
  const [newReportTitle, setNewReportTitle] = useState<string>('');

  const [newCvAgentId, setNewCvAgentId] = useState<number>(5);
  const [newCvValue, setNewCvValue] = useState<string>('integrity');
  const [newCvScore, setNewCvScore] = useState<number>(85);
  const [newCvEvidence, setNewCvEvidence] = useState<string>('');

  const isOwnerOrExecutive = currentUser?.roles?.some(r => ['human_owner', 'owner', 'superadmin', 'director_ai'].includes(r)) ?? true;

  const loadData = useCallback(async () => {
    try {
      setLoading(true);
      setErrorMsg(null);

      const [
        scorecardData,
        pulseData,
        catsData,
        kpisData,
        execData,
        divsData,
        portfolioData,
        evalsData,
        cvSummaryData,
        cyclesData,
        pipsData,
        bonusesData,
        reportsData,
      ] = await Promise.all([
        performanceService.getCorporateScorecard(),
        performanceService.getOperationalPulse(),
        performanceService.getCategories(),
        performanceService.getKpis(),
        performanceService.getExecutiveOverview(),
        performanceService.getDivisionalComparison(),
        performanceService.getProjectPortfolio(),
        performanceService.getEvaluations(),
        performanceService.getCoreValuesSummary(),
        performanceService.getCycles(),
        performanceService.getPlans(),
        performanceService.getBonuses(),
        performanceService.getReports(),
      ]);

      setScorecard(scorecardData);
      setPulse(pulseData);
      setCategories(catsData);
      setKpis(kpisData);
      setExecOverview(execData);
      setDivisions(divsData);
      setPortfolio(portfolioData);
      setEvaluations(evalsData);
      setCoreValuesSummary(cvSummaryData);
      setCycles(cyclesData);
      setPips(pipsData);
      setBonuses(bonusesData);
      setReports(reportsData);

      if (reportsData.length > 0 && !selectedReport) {
        setSelectedReport(reportsData[0]);
      }
    } catch (err: any) {
      setErrorMsg(err.message || 'Failed to load operational performance management data.');
    } finally {
      setLoading(false);
    }
  }, [selectedReport]);

  useEffect(() => {
    loadData();
  }, [loadData]);

  const loadAgentCoreValues = async (agentId: number) => {
    try {
      setSelectedAgentId(agentId);
      const res = await performanceService.getAgentCoreValues(agentId);
      setAgentCoreValues(res);
    } catch (err: any) {
      setErrorMsg(err.message || 'Failed to load agent core values.');
    }
  };

  // KPI Actions
  const handleCalculateAllKpis = async () => {
    try {
      setLoading(true);
      await performanceService.calculateAllKpis();
      setSuccessMsg('All active KPIs recalculated with verifiable transaction records.');
      await loadData();
    } catch (err: any) {
      setErrorMsg(err.message || 'Recalculation failed.');
    } finally {
      setLoading(false);
    }
  };

  const handleCreateKpi = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      await performanceService.createKpi({
        kpi_code: newKpiCode,
        name: newKpiName,
        category_id: newKpiCategory,
        performance_level: newKpiLevel,
        target_value: newKpiTarget,
        formula_expression: newKpiFormula,
        unit_of_measure: 'percentage',
        data_source: 'workforce_tasks',
        measurement_frequency: 'monthly',
        status: 'active',
        is_active: 1,
      });
      setShowKpiModal(false);
      setSuccessMsg(`KPI ${newKpiCode} created successfully.`);
      await loadData();
    } catch (err: any) {
      setErrorMsg(err.message || 'Failed to create KPI.');
    }
  };

  // Workflow Cycle Actions
  const handleTransitionCycle = async (cycleId: number, targetStatus: string) => {
    try {
      await performanceService.transitionCycle(cycleId, targetStatus);
      setSuccessMsg(`Cycle transition to '${targetStatus}' successful.`);
      await loadData();
    } catch (err: any) {
      setErrorMsg(err.message || 'Failed to transition cycle status.');
    }
  };

  const handleFinalizeEvaluation = async (evalId: number) => {
    try {
      await performanceService.finalizeEvaluation(evalId, 'Finalized and ratified by Executive Reviewer');
      setSuccessMsg(`Evaluation #${evalId} finalized with immutable SHA-256 audit hash.`);
      await loadData();
    } catch (err: any) {
      setErrorMsg(err.message || 'Failed to finalize evaluation.');
    }
  };

  // PIP Actions
  const handleCreatePip = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      await performanceService.createPlan({
        target_type: 'agent',
        target_id: newPipTargetId,
        responsible_agent_id: newPipTargetId,
        objective: newPipObjective,
        identified_gap: newPipGap,
        recommended_action: newPipAction,
        status: 'proposed',
      });
      setShowPipModal(false);
      setSuccessMsg('Performance Improvement Plan proposed successfully.');
      await loadData();
    } catch (err: any) {
      setErrorMsg(err.message || 'Failed to create PIP.');
    }
  };

  const handleTransitionPip = async (id: number, status: string) => {
    try {
      await performanceService.transitionPlan(id, status);
      setSuccessMsg(`PIP status transitioned to '${status}'.`);
      await loadData();
    } catch (err: any) {
      setErrorMsg(err.message || 'Failed to transition PIP status.');
    }
  };

  // Welfare Bonus Actions
  const handleRecommendBonus = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      await performanceService.recommendBonus({
        agent_id: newBonusAgentId,
        recommended_ticket_bonus: newBonusTickets,
        financial_impact_idr: newBonusAmountIdr,
        justification: newBonusJustification,
        bonus_category: 'high_complexity_delivery',
      });
      setShowBonusModal(false);
      setSuccessMsg('Welfare Bonus recommended for executive governance.');
      await loadData();
    } catch (err: any) {
      setErrorMsg(err.message || 'Failed to recommend bonus.');
    }
  };

  const handleApproveBonus = async (bonusId: number) => {
    try {
      await performanceService.approveBonus(bonusId);
      setSuccessMsg(`Welfare bonus reference approved under strict DoA gatekeeper.`);
      await loadData();
    } catch (err: any) {
      setErrorMsg(err.message || 'Failed to approve bonus. Check Delegated Authority limits.');
    }
  };

  const handleDisburseBonus = async (bonusId: number) => {
    try {
      await performanceService.disburseBonus(bonusId);
      setSuccessMsg(`Welfare bonus disbursed to agent.`);
      await loadData();
    } catch (err: any) {
      setErrorMsg(err.message || 'Failed to disburse bonus.');
    }
  };

  // Executive Reporting Actions
  const handleGenerateReport = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      const rep = await performanceService.generateReport({
        report_type: newReportType as any,
        title: newReportTitle || `Executive ${newReportType} Report`,
      });
      setShowReportModal(false);
      setSelectedReport(rep);
      setSuccessMsg('Executive Report generated with live analytical aggregations.');
      await loadData();
    } catch (err: any) {
      setErrorMsg(err.message || 'Failed to generate report.');
    }
  };

  const handlePublishReport = async (reportId: number) => {
    try {
      const rep = await performanceService.publishReport(reportId);
      setSelectedReport(rep);
      setSuccessMsg('Executive Report published officially.');
      await loadData();
    } catch (err: any) {
      setErrorMsg(err.message || 'Failed to publish report.');
    }
  };

  const handleRatifyReport = async (reportId: number) => {
    try {
      const rep = await performanceService.ratifyReport(reportId);
      setSelectedReport(rep);
      setSuccessMsg('Executive Report ratified by Human Owner.');
      await loadData();
    } catch (err: any) {
      setErrorMsg(err.message || 'Failed to ratify report.');
    }
  };

  // Core Value Assessment Action
  const handleRecordCoreValue = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      await performanceService.assessCoreValue({
        agent_id: newCvAgentId,
        core_value: newCvValue as any,
        rating_score: newCvScore,
        behavioral_evidence: newCvEvidence,
      });
      setShowCoreValueModal(false);
      setSuccessMsg('Core Value assessment recorded with qualitative evidence.');
      await loadData();
      await loadAgentCoreValues(newCvAgentId);
    } catch (err: any) {
      setErrorMsg(err.message || 'Failed to record Core Value assessment.');
    }
  };

  const getScoreBandBadge = (band: ScoreBand) => {
    switch (band) {
      case 'exceptional':
        return <span className="px-2 py-0.5 rounded text-xs font-semibold bg-emerald-900/60 text-emerald-300 border border-emerald-500/40">Exceptional (90-100)</span>;
      case 'exceeds_expectations':
        return <span className="px-2 py-0.5 rounded text-xs font-semibold bg-teal-900/60 text-teal-300 border border-teal-500/40">Exceeds (80-89)</span>;
      case 'meets_expectations':
        return <span className="px-2 py-0.5 rounded text-xs font-semibold bg-blue-900/60 text-blue-300 border border-blue-500/40">Meets (70-79)</span>;
      case 'needs_improvement':
        return <span className="px-2 py-0.5 rounded text-xs font-semibold bg-amber-900/60 text-amber-300 border border-amber-500/40">Needs Improvement (50-69)</span>;
      case 'unsatisfactory':
        return <span className="px-2 py-0.5 rounded text-xs font-semibold bg-rose-900/60 text-rose-300 border border-rose-500/40">Unsatisfactory (&lt;50)</span>;
      default:
        return <span className="px-2 py-0.5 rounded text-xs font-semibold bg-slate-800 text-slate-300">{band}</span>;
    }
  };

  const filteredKpis = kpis.filter(k => {
    if (selectedKpiCategory !== 'all' && k.category_code !== selectedKpiCategory) return false;
    return true;
  });

  const filteredEvaluations = evaluations.filter(e => {
    if (selectedScoreBand !== 'all' && e.score_band !== selectedScoreBand) return false;
    return true;
  });

  return (
    <div className="min-h-screen bg-slate-950 text-slate-100 p-4 md:p-8 space-y-6">
      {/* Header Banner */}
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-800 pb-5">
        <div>
          <div className="flex items-center gap-3">
            <h1 className="text-2xl md:text-3xl font-bold tracking-tight text-white flex items-center gap-2">
              <span className="p-2 rounded-lg bg-indigo-600/20 text-indigo-400 border border-indigo-500/30">
                📊
              </span>
              Operational Performance Management
            </h1>
            <span className="px-2.5 py-1 rounded-full text-xs font-semibold bg-indigo-950 text-indigo-300 border border-indigo-800">
              Prompt 29 Subsystem
            </span>
          </div>
          <p className="text-sm text-slate-400 mt-1">
            Authoritative KPI Framework, Multi-Tier Scorecards, AI Workforce Evaluation &amp; Executive Reporting
          </p>
        </div>

        <div className="flex items-center gap-2 flex-wrap">
          <button
            onClick={handleCalculateAllKpis}
            disabled={loading}
            className="px-3.5 py-2 text-xs font-medium rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white transition-colors flex items-center gap-1.5 shadow-sm shadow-indigo-950"
          >
            ⚡ Recalculate Live KPIs
          </button>
          <button
            onClick={() => setShowReportModal(true)}
            className="px-3.5 py-2 text-xs font-medium rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 transition-colors flex items-center gap-1.5"
          >
            📑 Generate Report
          </button>
          <button
            onClick={loadData}
            disabled={loading}
            className="p-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700 transition-colors"
            title="Refresh Data"
          >
            🔄
          </button>
        </div>
      </div>

      {/* Notifications */}
      {errorMsg && (
        <div className="p-3.5 rounded-lg bg-rose-950/80 border border-rose-700/60 text-rose-200 text-sm flex items-center justify-between">
          <span>⚠️ {errorMsg}</span>
          <button onClick={() => setErrorMsg(null)} className="text-rose-400 hover:text-white font-bold text-xs">✕</button>
        </div>
      )}
      {successMsg && (
        <div className="p-3.5 rounded-lg bg-emerald-950/80 border border-emerald-700/60 text-emerald-200 text-sm flex items-center justify-between">
          <span>✅ {successMsg}</span>
          <button onClick={() => setSuccessMsg(null)} className="text-emerald-400 hover:text-white font-bold text-xs">✕</button>
        </div>
      )}

      {/* Top Pulse Stats Overview */}
      {pulse && scorecard && (
        <div className="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3">
          <div className="p-4 rounded-xl bg-slate-900 border border-slate-800">
            <div className="text-xs text-slate-400 uppercase tracking-wider font-semibold">Corporate Health</div>
            <div className="text-2xl font-bold text-emerald-400 mt-1">
              {scorecard.corporate_composite_index}%
            </div>
            <div className="text-xs text-slate-400 capitalize mt-0.5">{scorecard.health_rating} Rating</div>
          </div>
          <div className="p-4 rounded-xl bg-slate-900 border border-slate-800">
            <div className="text-xs text-slate-400 uppercase tracking-wider font-semibold">Active Workforce</div>
            <div className="text-2xl font-bold text-indigo-400 mt-1">
              {pulse.active_agent_count} Agents
            </div>
            <div className="text-xs text-slate-400 mt-0.5">5/2 Roster Scheduled</div>
          </div>
          <div className="p-4 rounded-xl bg-slate-900 border border-slate-800">
            <div className="text-xs text-slate-400 uppercase tracking-wider font-semibold">Task Completion</div>
            <div className="text-2xl font-bold text-teal-400 mt-1">
              {pulse.task_completion_rate}%
            </div>
            <div className="text-xs text-slate-400 mt-0.5">{scorecard.operational_pulse.completed_tasks} / {scorecard.operational_pulse.total_tasks} Tasks</div>
          </div>
          <div className="p-4 rounded-xl bg-slate-900 border border-slate-800">
            <div className="text-xs text-slate-400 uppercase tracking-wider font-semibold">Deliverable Quality</div>
            <div className="text-2xl font-bold text-cyan-400 mt-1">
              {pulse.deliverable_acceptance}%
            </div>
            <div className="text-xs text-slate-400 mt-0.5">Peer Verified</div>
          </div>
          <div className="p-4 rounded-xl bg-slate-900 border border-slate-800">
            <div className="text-xs text-slate-400 uppercase tracking-wider font-semibold">DoA Compliance</div>
            <div className="text-2xl font-bold text-amber-400 mt-1">
              100%
            </div>
            <div className="text-xs text-slate-400 mt-0.5">Zero Level A Breaches</div>
          </div>
          <div className="p-4 rounded-xl bg-slate-900 border border-slate-800">
            <div className="text-xs text-slate-400 uppercase tracking-wider font-semibold">Active Portfolios</div>
            <div className="text-2xl font-bold text-violet-400 mt-1">
              {pulse.active_projects} Projects
            </div>
            <div className="text-xs text-slate-400 mt-0.5">Customer &amp; Internal</div>
          </div>
        </div>
      )}

      {/* Operational Navigation Tabs */}
      <div className="border-b border-slate-800 flex items-center gap-1 overflow-x-auto text-sm pb-1">
        {[
          { key: 'scorecard', label: 'Overview & Scorecard', icon: '📈' },
          { key: 'catalog', label: 'KPI Catalog (10)', icon: '📐' },
          { key: 'executive', label: 'Executive Performance', icon: '🏛️' },
          { key: 'divisions', label: 'Divisions & Units (6)', icon: '🏢' },
          { key: 'evaluations', label: 'AI Workforce Evaluations', icon: '🤖' },
          { key: 'core_values', label: 'Core Values (7)', icon: '💎' },
          { key: 'cycles', label: 'Workflow Cycles', icon: '🔄' },
          { key: 'pips', label: 'Improvement Plans', icon: '🛠️' },
          { key: 'governance', label: 'DoA Bonuses & Reports', icon: '⚖️' },
        ].map(t => (
          <button
            key={t.key}
            onClick={() => setActiveTab(t.key as any)}
            className={`px-3.5 py-2 font-medium rounded-t-lg transition-colors whitespace-nowrap flex items-center gap-1.5 ${
              activeTab === t.key
                ? 'bg-slate-900 text-indigo-400 border-b-2 border-indigo-500 font-semibold'
                : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900/50'
            }`}
          >
            <span>{t.icon}</span>
            <span>{t.label}</span>
          </button>
        ))}
      </div>

      {/* Tab 1: Corporate Scorecard */}
      {activeTab === 'scorecard' && scorecard && (
        <div className="space-y-6">
          <div className="bg-slate-900 rounded-xl border border-slate-800 p-5">
            <div className="flex items-center justify-between mb-4">
              <div>
                <h2 className="text-lg font-semibold text-white">Corporate Strategic Performance Scorecard</h2>
                <p className="text-xs text-slate-400">Rolled up from verifiable transaction ledgers for period {scorecard.evaluation_period}</p>
              </div>
              <div className="flex items-center gap-2">
                <span className="text-xs text-slate-400">Composite Corporate Index:</span>
                <span className="text-lg font-bold text-emerald-400">{scorecard.corporate_composite_index}%</span>
              </div>
            </div>

            <div className="overflow-x-auto">
              <table className="w-full text-left text-xs border-collapse">
                <thead>
                  <tr className="border-b border-slate-800 text-slate-400 uppercase tracking-wider bg-slate-950/60">
                    <th className="py-2.5 px-3">KPI Code</th>
                    <th className="py-2.5 px-3">Indicator Name</th>
                    <th className="py-2.5 px-3">Category</th>
                    <th className="py-2.5 px-3">Tier</th>
                    <th className="py-2.5 px-3 text-right">Target</th>
                    <th className="py-2.5 px-3 text-right">Actual</th>
                    <th className="py-2.5 px-3 text-right">Variance</th>
                    <th className="py-2.5 px-3 text-right">Attainment</th>
                    <th className="py-2.5 px-3 text-center">Status</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-800/60 text-slate-300">
                  {scorecard.scorecard_categories.map(m => (
                    <tr key={m.kpi_id} className="hover:bg-slate-800/40">
                      <td className="py-2.5 px-3 font-mono font-semibold text-indigo-300">{m.kpi_code}</td>
                      <td className="py-2.5 px-3 font-medium text-white">{m.kpi_name}</td>
                      <td className="py-2.5 px-3 text-slate-400">{m.category_name}</td>
                      <td className="py-2.5 px-3 capitalize text-slate-300">{m.performance_level}</td>
                      <td className="py-2.5 px-3 text-right font-mono">{m.target_value} {m.unit_of_measure}</td>
                      <td className="py-2.5 px-3 text-right font-mono font-bold text-white">{m.actual_value} {m.unit_of_measure}</td>
                      <td className={`py-2.5 px-3 text-right font-mono ${m.variance >= 0 ? 'text-emerald-400' : 'text-rose-400'}`}>
                        {m.variance >= 0 ? `+${m.variance}` : m.variance}
                      </td>
                      <td className="py-2.5 px-3 text-right font-mono font-semibold text-indigo-300">{m.attainment_pct}%</td>
                      <td className="py-2.5 px-3 text-center">
                        <span className={`px-2 py-0.5 rounded text-[10px] font-semibold uppercase ${
                          m.status === 'on_target' ? 'bg-emerald-950 text-emerald-300 border border-emerald-800' : 'bg-amber-950 text-amber-300 border border-amber-800'
                        }`}>
                          {m.status}
                        </span>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      )}

      {/* Tab 2: KPI Catalog */}
      {activeTab === 'catalog' && (
        <div className="space-y-4">
          <div className="flex flex-col md:flex-row md:items-center justify-between gap-3 bg-slate-900 p-4 rounded-xl border border-slate-800">
            <div className="flex items-center gap-3">
              <label className="text-xs text-slate-400">Filter Category:</label>
              <select
                value={selectedKpiCategory}
                onChange={e => setSelectedKpiCategory(e.target.value)}
                className="bg-slate-950 text-slate-200 text-xs px-2.5 py-1.5 rounded-lg border border-slate-800 focus:outline-none focus:border-indigo-500"
              >
                <option value="all">All 10 Categories</option>
                {categories.map(c => (
                  <option key={c.id} value={c.category_code}>{c.category_code} — {c.name}</option>
                ))}
              </select>
            </div>
            <button
              onClick={() => setShowKpiModal(true)}
              className="px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-medium"
            >
              + Define New KPI
            </button>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            {filteredKpis.map(k => (
              <div key={k.id} className="bg-slate-900 rounded-xl border border-slate-800 p-4 space-y-3">
                <div className="flex items-start justify-between">
                  <div>
                    <span className="font-mono text-xs font-bold text-indigo-400 bg-indigo-950/80 px-2 py-0.5 rounded border border-indigo-800/60">
                      {k.kpi_code}
                    </span>
                    <h3 className="text-sm font-semibold text-white mt-1.5">{k.name}</h3>
                  </div>
                  <span className="text-[10px] font-semibold uppercase px-2 py-0.5 rounded bg-slate-800 text-slate-300">
                    {k.performance_level}
                  </span>
                </div>
                <p className="text-xs text-slate-400 line-clamp-2">{k.description}</p>
                <div className="p-2.5 rounded-lg bg-slate-950/80 border border-slate-800/60 font-mono text-[11px] text-slate-300">
                  <span className="text-slate-500">Formula:</span> {k.formula_expression}
                </div>
                <div className="grid grid-cols-3 gap-2 text-xs text-slate-400 border-t border-slate-800 pt-2.5">
                  <div>
                    <div className="text-[10px] text-slate-500">Target</div>
                    <div className="font-bold text-white">{k.target_value} {k.unit_of_measure}</div>
                  </div>
                  <div>
                    <div className="text-[10px] text-slate-500">Frequency</div>
                    <div className="capitalize">{k.measurement_frequency}</div>
                  </div>
                  <div>
                    <div className="text-[10px] text-slate-500">Source</div>
                    <div className="font-mono text-[10px] truncate">{k.data_source}</div>
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* Tab 3: Executive Performance */}
      {activeTab === 'executive' && execOverview && (
        <div className="space-y-6">
          <div className="grid grid-cols-1 lg:grid-cols-3 gap-5">
            {/* Human Owner */}
            <div className="bg-slate-900 rounded-xl border border-slate-800 p-5 space-y-4">
              <div className="flex items-center gap-2">
                <span className="p-2 rounded-lg bg-indigo-950 text-indigo-400 border border-indigo-800">👑</span>
                <div>
                  <h3 className="font-bold text-white text-sm">Human Owner</h3>
                  <div className="text-xs text-indigo-300">Strategic Direction &amp; Ratification</div>
                </div>
              </div>
              <p className="text-xs text-slate-400">{execOverview.mandates.human_owner.mandate}</p>
              <div className="space-y-2 border-t border-slate-800 pt-3 text-xs">
                <div className="flex justify-between text-slate-300">
                  <span>Directives Issued:</span>
                  <span className="font-bold text-white">{execOverview.mandates.human_owner.total_directives_issued}</span>
                </div>
                <div className="flex justify-between text-slate-300">
                  <span>Acknowledgement Rate:</span>
                  <span className="font-bold text-emerald-400">{execOverview.mandates.human_owner.acknowledgement_rate_pct}%</span>
                </div>
                <div className="flex justify-between text-slate-300">
                  <span>Financial Approval Gate:</span>
                  <span className="font-bold text-amber-400">&gt; IDR 5,000,000 (Level A)</span>
                </div>
              </div>
            </div>

            {/* Continuous Executive Board */}
            <div className="bg-slate-900 rounded-xl border border-slate-800 p-5 space-y-4">
              <div className="flex items-center gap-2">
                <span className="p-2 rounded-lg bg-teal-950 text-teal-400 border border-teal-800">🏛️</span>
                <div>
                  <h3 className="font-bold text-white text-sm">Executive Board</h3>
                  <div className="text-xs text-teal-300">Continuous Operations &amp; Autonomous Governance</div>
                </div>
              </div>
              <p className="text-xs text-slate-400">{execOverview.mandates.executive_board.mandate}</p>
              <div className="space-y-2 border-t border-slate-800 pt-3 text-xs">
                <div className="flex justify-between text-slate-300">
                  <span>Decisions Ratified:</span>
                  <span className="font-bold text-white">{execOverview.mandates.executive_board.total_decisions_proposed}</span>
                </div>
                <div className="flex justify-between text-slate-300">
                  <span>Ratification Velocity:</span>
                  <span className="font-bold text-emerald-400">{execOverview.mandates.executive_board.ratification_rate_pct}%</span>
                </div>
                <div className="flex justify-between text-slate-300">
                  <span>Autonomous Limit:</span>
                  <span className="font-bold text-cyan-400">&le; IDR 5,000,000 (Level B)</span>
                </div>
              </div>
            </div>

            {/* Director AI */}
            <div className="bg-slate-900 rounded-xl border border-slate-800 p-5 space-y-4">
              <div className="flex items-center gap-2">
                <span className="p-2 rounded-lg bg-cyan-950 text-cyan-400 border border-cyan-800">⚡</span>
                <div>
                  <h3 className="font-bold text-white text-sm">Director AI</h3>
                  <div className="text-xs text-cyan-300">Chief Operational AI Executive</div>
                </div>
              </div>
              <p className="text-xs text-slate-400">{execOverview.mandates.director_ai.mandate}</p>
              <div className="space-y-2 border-t border-slate-800 pt-3 text-xs">
                <div className="flex justify-between text-slate-300">
                  <span>Workforce Managed:</span>
                  <span className="font-bold text-white">{execOverview.mandates.director_ai.active_workforce_managed} AI Agents</span>
                </div>
                <div className="flex justify-between text-slate-300">
                  <span>Tasks Orchestrated:</span>
                  <span className="font-bold text-emerald-400">{execOverview.mandates.director_ai.tasks_orchestrated}</span>
                </div>
                <div className="flex justify-between text-slate-300">
                  <span>5/2 Shift Continuity:</span>
                  <span className="font-bold text-teal-400">100% Zero Violation</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Tab 4: Divisions & Units */}
      {activeTab === 'divisions' && (
        <div className="space-y-4">
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            {divisions.map(d => (
              <div key={d.division_id} className="bg-slate-900 rounded-xl border border-slate-800 p-5 space-y-3">
                <div className="flex items-start justify-between">
                  <div>
                    <span className="font-mono text-xs font-semibold text-slate-400">{d.unit_code}</span>
                    <h3 className="text-base font-bold text-white">{d.name}</h3>
                  </div>
                  <span className={`px-2 py-0.5 rounded text-[10px] font-semibold uppercase ${
                    d.health_status === 'high_performing' ? 'bg-emerald-950 text-emerald-300 border border-emerald-800' : 'bg-blue-950 text-blue-300 border border-blue-800'
                  }`}>
                    {d.health_status}
                  </span>
                </div>
                <div className="grid grid-cols-2 gap-2 text-xs border-y border-slate-800/80 py-2.5">
                  <div>
                    <span className="text-slate-400">Headcount:</span>
                    <div className="font-bold text-white">{d.active_headcount} Agents</div>
                  </div>
                  <div>
                    <span className="text-slate-400">Tasks Completed:</span>
                    <div className="font-bold text-white">{d.completed_tasks} / {d.total_tasks}</div>
                  </div>
                  <div>
                    <span className="text-slate-400">Completion Rate:</span>
                    <div className="font-bold text-emerald-400">{d.completion_rate}%</div>
                  </div>
                  <div>
                    <span className="text-slate-400">Quality Index:</span>
                    <div className="font-bold text-indigo-400">{d.quality_rate}%</div>
                  </div>
                </div>
                <div className="flex items-center justify-between text-xs pt-1">
                  <span className="text-slate-400">Composite Score:</span>
                  <span className="text-base font-bold text-emerald-400">{d.performance_score}/100</span>
                </div>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* Tab 5: AI Workforce Evaluations */}
      {activeTab === 'evaluations' && (
        <div className="space-y-4">
          <div className="flex items-center justify-between bg-slate-900 p-4 rounded-xl border border-slate-800">
            <div className="flex items-center gap-3">
              <label className="text-xs text-slate-400">Filter Band:</label>
              <select
                value={selectedScoreBand}
                onChange={e => setSelectedScoreBand(e.target.value)}
                className="bg-slate-950 text-slate-200 text-xs px-2.5 py-1.5 rounded-lg border border-slate-800 focus:outline-none"
              >
                <option value="all">All Score Bands</option>
                <option value="exceptional">Exceptional (90-100)</option>
                <option value="exceeds_expectations">Exceeds (80-89)</option>
                <option value="meets_expectations">Meets (70-79)</option>
                <option value="needs_improvement">Needs Improvement (50-69)</option>
                <option value="unsatisfactory">Unsatisfactory (&lt;50)</option>
              </select>
            </div>
            <button
              onClick={() => setShowBonusModal(true)}
              className="px-3 py-1.5 rounded-lg bg-amber-600 hover:bg-amber-500 text-white text-xs font-medium"
            >
              ⭐ Recommend Bonus Reference
            </button>
          </div>

          <div className="overflow-x-auto bg-slate-900 rounded-xl border border-slate-800">
            <table className="w-full text-left text-xs border-collapse">
              <thead>
                <tr className="border-b border-slate-800 text-slate-400 uppercase tracking-wider bg-slate-950/60">
                  <th className="py-2.5 px-3">Evaluation Code</th>
                  <th className="py-2.5 px-3">Agent</th>
                  <th className="py-2.5 px-3">Division</th>
                  <th className="py-2.5 px-3 text-right">Composite</th>
                  <th className="py-2.5 px-3 text-right">Quality</th>
                  <th className="py-2.5 px-3 text-right">Delivery</th>
                  <th className="py-2.5 px-3 text-right">Core Values</th>
                  <th className="py-2.5 px-3">Score Band</th>
                  <th className="py-2.5 px-3">Audit Hash</th>
                  <th className="py-2.5 px-3 text-center">Action</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-800/60 text-slate-300">
                {filteredEvaluations.map(e => (
                  <tr key={e.id} className="hover:bg-slate-800/40">
                    <td className="py-2.5 px-3 font-mono font-semibold text-indigo-300">{e.evaluation_code}</td>
                    <td className="py-2.5 px-3 font-medium text-white">{e.agent_name || `Agent #${e.agent_id}`}</td>
                    <td className="py-2.5 px-3 text-slate-400">{e.unit_name || 'Technology Division'}</td>
                    <td className="py-2.5 px-3 text-right font-mono font-bold text-white text-sm">{e.composite_score}</td>
                    <td className="py-2.5 px-3 text-right font-mono text-emerald-400">{e.quality_score}</td>
                    <td className="py-2.5 px-3 text-right font-mono text-teal-400">{e.delivery_score}</td>
                    <td className="py-2.5 px-3 text-right font-mono text-cyan-400">{e.core_values_score}</td>
                    <td className="py-2.5 px-3">{getScoreBandBadge(e.score_band)}</td>
                    <td className="py-2.5 px-3 font-mono text-[10px] text-slate-400" title={e.audit_hash}>
                      {e.audit_hash ? `${e.audit_hash.substring(0, 10)}...` : 'Pending'}
                    </td>
                    <td className="py-2.5 px-3 text-center">
                      {e.status !== 'finalized' && isOwnerOrExecutive && (
                        <button
                          onClick={() => handleFinalizeEvaluation(e.id)}
                          className="px-2 py-1 rounded bg-indigo-600/30 text-indigo-300 hover:bg-indigo-600 hover:text-white border border-indigo-500/40 text-[10px]"
                        >
                          Finalize
                        </button>
                      )}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {/* Tab 6: Core Values */}
      {activeTab === 'core_values' && (
        <div className="space-y-6">
          <div className="flex items-center justify-between bg-slate-900 p-4 rounded-xl border border-slate-800">
            <div>
              <h3 className="font-semibold text-white text-sm">7 MCE Core Values Framework</h3>
              <p className="text-xs text-slate-400">Qualitative Behavioral Assessments based on Verified Task Outcomes</p>
            </div>
            <button
              onClick={() => setShowCoreValueModal(true)}
              className="px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-medium"
            >
              + Record Assessment
            </button>
          </div>

          {coreValuesSummary && (
            <div className="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-3">
              {Object.entries(coreValuesSummary.dimensions).map(([key, val]) => (
                <div key={key} className="p-3.5 rounded-xl bg-slate-900 border border-slate-800 space-y-1">
                  <div className="text-[10px] uppercase font-bold text-slate-400 tracking-wider capitalize">
                    {key.replace('_', ' ')}
                  </div>
                  <div className="text-xl font-bold text-white mt-1">
                    {val.avg_score}%
                  </div>
                  <div className="text-[10px] text-slate-500">{val.count} logged events</div>
                </div>
              ))}
            </div>
          )}
        </div>
      )}

      {/* Tab 7: Workflow Cycles */}
      {activeTab === 'cycles' && (
        <div className="space-y-4">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            {cycles.map(c => (
              <div key={c.id} className="bg-slate-900 rounded-xl border border-slate-800 p-5 space-y-4">
                <div className="flex items-start justify-between">
                  <div>
                    <span className="font-mono text-xs font-bold text-indigo-400">{c.cycle_code}</span>
                    <h3 className="text-base font-bold text-white mt-1">{c.title}</h3>
                  </div>
                  <span className="px-2 py-0.5 rounded text-xs font-semibold bg-slate-800 text-slate-300 uppercase">
                    {c.status}
                  </span>
                </div>
                <div className="text-xs text-slate-400 flex items-center gap-4">
                  <span>Period: {c.period_start} to {c.period_end}</span>
                  <span>Evaluated: {c.total_agents_evaluated} Agents</span>
                </div>
                <div className="flex items-center gap-2 pt-2 border-t border-slate-800 text-xs">
                  <span className="text-slate-500">Stage Control:</span>
                  {c.status === 'open' && (
                    <button
                      onClick={() => handleTransitionCycle(c.id, 'data_collection')}
                      className="px-2.5 py-1 rounded bg-indigo-600/30 text-indigo-300 hover:bg-indigo-600 hover:text-white"
                    >
                      Collect Data &rarr;
                    </button>
                  )}
                  {c.status === 'data_collection' && (
                    <button
                      onClick={() => handleTransitionCycle(c.id, 'kpi_calculation')}
                      className="px-2.5 py-1 rounded bg-indigo-600/30 text-indigo-300 hover:bg-indigo-600 hover:text-white"
                    >
                      Calculate KPIs &rarr;
                    </button>
                  )}
                  {c.status === 'kpi_calculation' && (
                    <button
                      onClick={() => handleTransitionCycle(c.id, 'review')}
                      className="px-2.5 py-1 rounded bg-teal-600/30 text-teal-300 hover:bg-teal-600 hover:text-white"
                    >
                      Executive Review &rarr;
                    </button>
                  )}
                </div>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* Tab 8: Improvement Plans (PIP) */}
      {activeTab === 'pips' && (
        <div className="space-y-4">
          <div className="flex items-center justify-between bg-slate-900 p-4 rounded-xl border border-slate-800">
            <div>
              <h3 className="font-semibold text-white text-sm">Performance Improvement Plans (PIPs)</h3>
              <p className="text-xs text-slate-400">Structured remediation linked to authoritative workforce tasks</p>
            </div>
            <button
              onClick={() => setShowPipModal(true)}
              className="px-3 py-1.5 rounded-lg bg-amber-600 hover:bg-amber-500 text-white text-xs font-medium"
            >
              + Propose Remediation Plan
            </button>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            {pips.map(p => (
              <div key={p.id} className="bg-slate-900 rounded-xl border border-slate-800 p-5 space-y-3">
                <div className="flex items-start justify-between">
                  <div>
                    <span className="font-mono text-xs font-bold text-amber-400">{p.plan_code}</span>
                    <h3 className="text-sm font-bold text-white mt-1">{p.objective}</h3>
                  </div>
                  <span className={`px-2 py-0.5 rounded text-[10px] font-semibold uppercase ${
                    p.status === 'completed' ? 'bg-emerald-950 text-emerald-300 border border-emerald-800' : 'bg-amber-950 text-amber-300 border border-amber-800'
                  }`}>
                    {p.status}
                  </span>
                </div>
                <div className="text-xs text-slate-300">
                  <span className="text-slate-500">Identified Deficit:</span> {p.identified_gap}
                </div>
                <div className="text-xs text-slate-300">
                  <span className="text-slate-500">Action Plan:</span> {p.recommended_action}
                </div>
                {p.bound_task_title && (
                  <div className="p-2 rounded bg-slate-950 font-mono text-xs text-indigo-300 border border-slate-800">
                    🔗 Task: {p.bound_task_title}
                  </div>
                )}
                <div className="space-y-1">
                  <div className="flex justify-between text-xs text-slate-400">
                    <span>Remediation Progress:</span>
                    <span>{p.progress_percent}%</span>
                  </div>
                  <div className="w-full bg-slate-800 rounded-full h-1.5 overflow-hidden">
                    <div className="bg-amber-500 h-1.5 rounded-full" style={{ width: `${p.progress_percent}%` }} />
                  </div>
                </div>
                <div className="flex items-center gap-2 pt-2 border-t border-slate-800 text-xs">
                  {p.status === 'proposed' && (
                    <button
                      onClick={() => handleTransitionPip(p.id, 'approved')}
                      className="px-2 py-1 rounded bg-indigo-600/30 text-indigo-300 hover:bg-indigo-600 hover:text-white"
                    >
                      Approve Plan
                    </button>
                  )}
                  {p.status === 'approved' && (
                    <button
                      onClick={() => handleTransitionPip(p.id, 'in_progress')}
                      className="px-2 py-1 rounded bg-teal-600/30 text-teal-300 hover:bg-teal-600 hover:text-white"
                    >
                      Start Execution
                    </button>
                  )}
                  {p.status === 'in_progress' && (
                    <button
                      onClick={() => handleTransitionPip(p.id, 'completed')}
                      className="px-2 py-1 rounded bg-emerald-600/30 text-emerald-300 hover:bg-emerald-600 hover:text-white"
                    >
                      Verify &amp; Complete
                    </button>
                  )}
                </div>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* Tab 9: DoA Bonuses & Reports */}
      {activeTab === 'governance' && (
        <div className="space-y-6">
          {/* Bonuses Section */}
          <div className="space-y-4">
            <div className="flex items-center justify-between bg-slate-900 p-4 rounded-xl border border-slate-800">
              <div>
                <h3 className="font-semibold text-white text-sm">Welfare Bonus References &amp; Financial Governance</h3>
                <p className="text-xs text-slate-400">Strict DoA Compliance: Level A (&gt; IDR 5M Owner Gate) vs Level B (&le; IDR 5M Autonomous)</p>
              </div>
              <button
                onClick={() => setShowBonusModal(true)}
                className="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-medium"
              >
                + Recommend Bonus
              </button>
            </div>

            <div className="overflow-x-auto bg-slate-900 rounded-xl border border-slate-800">
              <table className="w-full text-left text-xs border-collapse">
                <thead>
                  <tr className="border-b border-slate-800 text-slate-400 uppercase tracking-wider bg-slate-950/60">
                    <th className="py-2.5 px-3">Reference Code</th>
                    <th className="py-2.5 px-3">Agent</th>
                    <th className="py-2.5 px-3">Category</th>
                    <th className="py-2.5 px-3 text-right">Tickets</th>
                    <th className="py-2.5 px-3 text-right">Financial Impact</th>
                    <th className="py-2.5 px-3">DoA Level</th>
                    <th className="py-2.5 px-3">Status</th>
                    <th className="py-2.5 px-3 text-center">Action</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-800/60 text-slate-300">
                  {bonuses.map(b => (
                    <tr key={b.id} className="hover:bg-slate-800/40">
                      <td className="py-2.5 px-3 font-mono font-semibold text-emerald-300">{b.reference_code}</td>
                      <td className="py-2.5 px-3 font-medium text-white">{b.agent_name || `Agent #${b.agent_id}`}</td>
                      <td className="py-2.5 px-3 capitalize">{b.bonus_category.replace('_', ' ')}</td>
                      <td className="py-2.5 px-3 text-right font-mono font-bold text-white">{b.recommended_ticket_bonus}</td>
                      <td className="py-2.5 px-3 text-right font-mono font-bold text-amber-300">
                        IDR {Number(b.financial_impact_idr).toLocaleString()}
                      </td>
                      <td className="py-2.5 px-3">
                        <span className={`px-2 py-0.5 rounded text-[10px] font-semibold uppercase ${
                          b.financial_approval_level === 'level_a'
                            ? 'bg-rose-950 text-rose-300 border border-rose-800'
                            : 'bg-teal-950 text-teal-300 border border-teal-800'
                        }`}>
                          {b.financial_approval_level}
                        </span>
                      </td>
                      <td className="py-2.5 px-3 capitalize">{b.approval_status}</td>
                      <td className="py-2.5 px-3 text-center space-x-1">
                        {b.approval_status === 'pending' && isOwnerOrExecutive && (
                          <button
                            onClick={() => handleApproveBonus(b.id)}
                            className="px-2 py-1 rounded bg-emerald-600/30 text-emerald-300 hover:bg-emerald-600 hover:text-white text-[10px]"
                          >
                            Approve
                          </button>
                        )}
                        {b.approval_status === 'approved' && isOwnerOrExecutive && (
                          <button
                            onClick={() => handleDisburseBonus(b.id)}
                            className="px-2 py-1 rounded bg-cyan-600/30 text-cyan-300 hover:bg-cyan-600 hover:text-white text-[10px]"
                          >
                            Disburse
                          </button>
                        )}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>

          {/* Executive Reports Section */}
          <div className="space-y-4">
            <div className="flex items-center justify-between bg-slate-900 p-4 rounded-xl border border-slate-800">
              <div>
                <h3 className="font-semibold text-white text-sm">Executive Reports Archive</h3>
                <p className="text-xs text-slate-400">Published and Ratified C-Suite Operations Reports</p>
              </div>
              <button
                onClick={() => setShowReportModal(true)}
                className="px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-medium"
              >
                + Compile Report
              </button>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-5">
              <div className="space-y-2">
                {reports.map(r => (
                  <div
                    key={r.id}
                    onClick={() => setSelectedReport(r)}
                    className={`p-3.5 rounded-xl border cursor-pointer transition-colors ${
                      selectedReport?.id === r.id
                        ? 'bg-slate-800 border-indigo-500/80 shadow-md'
                        : 'bg-slate-900 border-slate-800 hover:border-slate-700'
                    }`}
                  >
                    <div className="flex items-center justify-between">
                      <span className="font-mono text-xs font-bold text-indigo-400">{r.report_code}</span>
                      <span className="text-[10px] uppercase font-semibold text-slate-400">{r.approval_status}</span>
                    </div>
                    <h4 className="text-sm font-semibold text-white mt-1">{r.title}</h4>
                    <p className="text-xs text-slate-400 line-clamp-1 mt-0.5">{r.executive_summary}</p>
                  </div>
                ))}
              </div>

              {selectedReport && (
                <div className="lg:col-span-2 bg-slate-900 rounded-xl border border-slate-800 p-5 space-y-4">
                  <div className="flex items-start justify-between border-b border-slate-800 pb-3">
                    <div>
                      <span className="font-mono text-xs font-semibold text-indigo-400">{selectedReport.report_code}</span>
                      <h3 className="text-base font-bold text-white mt-0.5">{selectedReport.title}</h3>
                      <div className="text-xs text-slate-400 mt-0.5">Author: {selectedReport.author_identity} &bull; Period: {selectedReport.period_start} to {selectedReport.period_end}</div>
                    </div>
                    <div className="flex items-center gap-1.5">
                      {selectedReport.approval_status === 'draft' && isOwnerOrExecutive && (
                        <button
                          onClick={() => handlePublishReport(selectedReport.id)}
                          className="px-2.5 py-1 text-xs rounded bg-indigo-600 text-white hover:bg-indigo-500"
                        >
                          Publish
                        </button>
                      )}
                      {selectedReport.approval_status === 'published' && isOwnerOrExecutive && (
                        <button
                          onClick={() => handleRatifyReport(selectedReport.id)}
                          className="px-2.5 py-1 text-xs rounded bg-emerald-600 text-white hover:bg-emerald-500"
                        >
                          Ratify (Owner)
                        </button>
                      )}
                    </div>
                  </div>

                  <div className="space-y-3 text-xs text-slate-300">
                    <div>
                      <h4 className="font-semibold text-slate-200 uppercase text-[10px] tracking-wider">Executive Summary</h4>
                      <p className="mt-1 leading-relaxed text-slate-300 bg-slate-950 p-3 rounded-lg border border-slate-800/80">
                        {selectedReport.executive_summary}
                      </p>
                    </div>

                    {selectedReport.highlights && selectedReport.highlights.length > 0 && (
                      <div>
                        <h4 className="font-semibold text-emerald-400 uppercase text-[10px] tracking-wider">Operational Highlights</h4>
                        <ul className="mt-1 space-y-1 list-disc list-inside text-slate-300">
                          {selectedReport.highlights.map((h, i) => (
                            <li key={i}>{h}</li>
                          ))}
                        </ul>
                      </div>
                    )}

                    {selectedReport.risks_and_issues && selectedReport.risks_and_issues.length > 0 && (
                      <div>
                        <h4 className="font-semibold text-amber-400 uppercase text-[10px] tracking-wider">Identified Risks &amp; Governance Notes</h4>
                        <ul className="mt-1 space-y-1 list-disc list-inside text-slate-300">
                          {selectedReport.risks_and_issues.map((risk, i) => (
                            <li key={i}>{risk}</li>
                          ))}
                        </ul>
                      </div>
                    )}
                  </div>
                </div>
              )}
            </div>
          </div>
        </div>
      )}

      {/* MODAL: Define New KPI */}
      {showKpiModal && (
        <div className="fixed inset-0 z-50 bg-black/80 flex items-center justify-center p-4">
          <div className="bg-slate-900 border border-slate-800 rounded-xl p-5 w-full max-w-lg space-y-4">
            <h3 className="text-base font-bold text-white">Define New KPI Indicator</h3>
            <form onSubmit={handleCreateKpi} className="space-y-3 text-xs">
              <div>
                <label className="text-slate-400">KPI Code</label>
                <input
                  type="text"
                  required
                  placeholder="e.g. KPI-DEV-001"
                  value={newKpiCode}
                  onChange={e => setNewKpiCode(e.target.value)}
                  className="w-full bg-slate-950 border border-slate-800 rounded-lg p-2 text-white mt-1"
                />
              </div>
              <div>
                <label className="text-slate-400">Indicator Name</label>
                <input
                  type="text"
                  required
                  value={newKpiName}
                  onChange={e => setNewKpiName(e.target.value)}
                  className="w-full bg-slate-950 border border-slate-800 rounded-lg p-2 text-white mt-1"
                />
              </div>
              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="text-slate-400">Category</label>
                  <select
                    value={newKpiCategory}
                    onChange={e => setNewKpiCategory(Number(e.target.value))}
                    className="w-full bg-slate-950 border border-slate-800 rounded-lg p-2 text-white mt-1"
                  >
                    {categories.map(c => (
                      <option key={c.id} value={c.id}>{c.name}</option>
                    ))}
                  </select>
                </div>
                <div>
                  <label className="text-slate-400">Performance Level</label>
                  <select
                    value={newKpiLevel}
                    onChange={e => setNewKpiLevel(e.target.value as any)}
                    className="w-full bg-slate-950 border border-slate-800 rounded-lg p-2 text-white mt-1"
                  >
                    <option value="corporate">Corporate</option>
                    <option value="executive">Executive</option>
                    <option value="organizational">Organizational</option>
                    <option value="workforce">Workforce</option>
                  </select>
                </div>
              </div>
              <div className="flex justify-end gap-2 pt-3 border-t border-slate-800">
                <button
                  type="button"
                  onClick={() => setShowKpiModal(false)}
                  className="px-3 py-1.5 rounded-lg bg-slate-800 text-slate-300"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="px-3 py-1.5 rounded-lg bg-indigo-600 text-white"
                >
                  Save KPI
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* MODAL: Propose PIP */}
      {showPipModal && (
        <div className="fixed inset-0 z-50 bg-black/80 flex items-center justify-center p-4">
          <div className="bg-slate-900 border border-slate-800 rounded-xl p-5 w-full max-w-lg space-y-4">
            <h3 className="text-base font-bold text-white">Propose Performance Improvement Plan</h3>
            <form onSubmit={handleCreatePip} className="space-y-3 text-xs">
              <div>
                <label className="text-slate-400">Target Agent ID</label>
                <input
                  type="number"
                  required
                  value={newPipTargetId}
                  onChange={e => setNewPipTargetId(Number(e.target.value))}
                  className="w-full bg-slate-950 border border-slate-800 rounded-lg p-2 text-white mt-1"
                />
              </div>
              <div>
                <label className="text-slate-400">Objective</label>
                <input
                  type="text"
                  required
                  placeholder="e.g. Elevate code review first-pass verification rate"
                  value={newPipObjective}
                  onChange={e => setNewPipObjective(e.target.value)}
                  className="w-full bg-slate-950 border border-slate-800 rounded-lg p-2 text-white mt-1"
                />
              </div>
              <div>
                <label className="text-slate-400">Identified Deficit</label>
                <textarea
                  required
                  rows={2}
                  value={newPipGap}
                  onChange={e => setNewPipGap(e.target.value)}
                  className="w-full bg-slate-950 border border-slate-800 rounded-lg p-2 text-white mt-1"
                />
              </div>
              <div>
                <label className="text-slate-400">Recommended Action</label>
                <textarea
                  required
                  rows={2}
                  value={newPipAction}
                  onChange={e => setNewPipAction(e.target.value)}
                  className="w-full bg-slate-950 border border-slate-800 rounded-lg p-2 text-white mt-1"
                />
              </div>
              <div className="flex justify-end gap-2 pt-3 border-t border-slate-800">
                <button
                  type="button"
                  onClick={() => setShowPipModal(false)}
                  className="px-3 py-1.5 rounded-lg bg-slate-800 text-slate-300"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="px-3 py-1.5 rounded-lg bg-amber-600 text-white"
                >
                  Propose Plan
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* MODAL: Recommend Welfare Bonus */}
      {showBonusModal && (
        <div className="fixed inset-0 z-50 bg-black/80 flex items-center justify-center p-4">
          <div className="bg-slate-900 border border-slate-800 rounded-xl p-5 w-full max-w-lg space-y-4">
            <h3 className="text-base font-bold text-white">Recommend Welfare Bonus Reference</h3>
            <form onSubmit={handleRecommendBonus} className="space-y-3 text-xs">
              <div>
                <label className="text-slate-400">Agent ID</label>
                <input
                  type="number"
                  required
                  value={newBonusAgentId}
                  onChange={e => setNewBonusAgentId(Number(e.target.value))}
                  className="w-full bg-slate-950 border border-slate-800 rounded-lg p-2 text-white mt-1"
                />
              </div>
              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="text-slate-400">Reward Tickets</label>
                  <input
                    type="number"
                    required
                    value={newBonusTickets}
                    onChange={e => setNewBonusTickets(Number(e.target.value))}
                    className="w-full bg-slate-950 border border-slate-800 rounded-lg p-2 text-white mt-1"
                  />
                </div>
                <div>
                  <label className="text-slate-400">Financial Impact (IDR)</label>
                  <input
                    type="number"
                    required
                    value={newBonusAmountIdr}
                    onChange={e => setNewBonusAmountIdr(Number(e.target.value))}
                    className="w-full bg-slate-950 border border-slate-800 rounded-lg p-2 text-white mt-1"
                  />
                  <div className="text-[10px] text-slate-500 mt-0.5">
                    {newBonusAmountIdr > 5000000 ? '⚠️ Level A (Owner Approval)' : '✅ Level B (Board Autonomous)'}
                  </div>
                </div>
              </div>
              <div>
                <label className="text-slate-400">Performance Justification</label>
                <textarea
                  required
                  rows={2}
                  value={newBonusJustification}
                  onChange={e => setNewBonusJustification(e.target.value)}
                  className="w-full bg-slate-950 border border-slate-800 rounded-lg p-2 text-white mt-1"
                />
              </div>
              <div className="flex justify-end gap-2 pt-3 border-t border-slate-800">
                <button
                  type="button"
                  onClick={() => setShowBonusModal(false)}
                  className="px-3 py-1.5 rounded-lg bg-slate-800 text-slate-300"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="px-3 py-1.5 rounded-lg bg-emerald-600 text-white"
                >
                  Submit Recommendation
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* MODAL: Generate Executive Report */}
      {showReportModal && (
        <div className="fixed inset-0 z-50 bg-black/80 flex items-center justify-center p-4">
          <div className="bg-slate-900 border border-slate-800 rounded-xl p-5 w-full max-w-lg space-y-4">
            <h3 className="text-base font-bold text-white">Compile Executive Report</h3>
            <form onSubmit={handleGenerateReport} className="space-y-3 text-xs">
              <div>
                <label className="text-slate-400">Report Type</label>
                <select
                  value={newReportType}
                  onChange={e => setNewReportType(e.target.value)}
                  className="w-full bg-slate-950 border border-slate-800 rounded-lg p-2 text-white mt-1"
                >
                  <option value="corporate_overview">Corporate Strategic Overview</option>
                  <option value="executive_operations">Executive Operations &amp; DoA</option>
                  <option value="organizational_unit">Organizational Unit Health</option>
                  <option value="project_portfolio">Project Portfolio Attainment</option>
                </select>
              </div>
              <div>
                <label className="text-slate-400">Title</label>
                <input
                  type="text"
                  required
                  placeholder="e.g. Executive Corporate Performance Report (Nov 2026)"
                  value={newReportTitle}
                  onChange={e => setNewReportTitle(e.target.value)}
                  className="w-full bg-slate-950 border border-slate-800 rounded-lg p-2 text-white mt-1"
                />
              </div>
              <div className="flex justify-end gap-2 pt-3 border-t border-slate-800">
                <button
                  type="button"
                  onClick={() => setShowReportModal(false)}
                  className="px-3 py-1.5 rounded-lg bg-slate-800 text-slate-300"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="px-3 py-1.5 rounded-lg bg-indigo-600 text-white"
                >
                  Compile &amp; Generate
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* MODAL: Record Core Value Assessment */}
      {showCoreValueModal && (
        <div className="fixed inset-0 z-50 bg-black/80 flex items-center justify-center p-4">
          <div className="bg-slate-900 border border-slate-800 rounded-xl p-5 w-full max-w-lg space-y-4">
            <h3 className="text-base font-bold text-white">Record Core Values Assessment</h3>
            <form onSubmit={handleRecordCoreValue} className="space-y-3 text-xs">
              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="text-slate-400">Agent ID</label>
                  <input
                    type="number"
                    required
                    value={newCvAgentId}
                    onChange={e => setNewCvAgentId(Number(e.target.value))}
                    className="w-full bg-slate-950 border border-slate-800 rounded-lg p-2 text-white mt-1"
                  />
                </div>
                <div>
                  <label className="text-slate-400">Core Value</label>
                  <select
                    value={newCvValue}
                    onChange={e => setNewCvValue(e.target.value)}
                    className="w-full bg-slate-950 border border-slate-800 rounded-lg p-2 text-white mt-1 capitalize"
                  >
                    {['integrity', 'innovation', 'collaboration', 'excellence', 'adaptability', 'customer_centric', 'sustainability'].map(v => (
                      <option key={v} value={v}>{v.replace('_', ' ')}</option>
                    ))}
                  </select>
                </div>
              </div>
              <div>
                <label className="text-slate-400">Rating Score (0-100)</label>
                <input
                  type="number"
                  min={0}
                  max={100}
                  required
                  value={newCvScore}
                  onChange={e => setNewCvScore(Number(e.target.value))}
                  className="w-full bg-slate-950 border border-slate-800 rounded-lg p-2 text-white mt-1"
                />
              </div>
              <div>
                <label className="text-slate-400">Qualitative Behavioral Evidence</label>
                <textarea
                  required
                  rows={2}
                  placeholder="Specific verifiable outcome or deliverable observed"
                  value={newCvEvidence}
                  onChange={e => setNewCvEvidence(e.target.value)}
                  className="w-full bg-slate-950 border border-slate-800 rounded-lg p-2 text-white mt-1"
                />
              </div>
              <div className="flex justify-end gap-2 pt-3 border-t border-slate-800">
                <button
                  type="button"
                  onClick={() => setShowCoreValueModal(false)}
                  className="px-3 py-1.5 rounded-lg bg-slate-800 text-slate-300"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="px-3 py-1.5 rounded-lg bg-indigo-600 text-white"
                >
                  Record Assessment
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};

export default PerformanceManagementView;
