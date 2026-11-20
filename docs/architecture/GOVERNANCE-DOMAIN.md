# MCE AI Office — Corporate Governance & Leadership Architecture

## 1. Governance Principles & Philosophy

The Corporate Governance Domain establishes authoritative decision-making rules, institutional mandates, and corporate direction tracking for the MCE AI Office monorepo.

A core tenet of the MCE architecture is balancing autonomous execution with robust oversight:
1. **Autonomy for Growth**: The Continuous Executive Board operates autonomously for day-to-day internal operations, campaigns, and business expansion without micromanagement from the Owner.
2. **Owner Strategic Gate**: The Human Owner maintains final strategic authority, capital expenditure veto, and Level A approval gates (> IDR 5.000.000).
3. **Values-Driven Operation**: All activities must align with the 7 Core Values of PT Multi Cipta Edukasi.

---

## 2. The 7 Core Values (Controlled Reference Data)

The 7 Core Values are seeded into `core_values` as immutable reference data:

| # | Code | English Name | Indonesian Name | Corporate Mandate |
|---|------|--------------|-----------------|-------------------|
| 1 | `integrity` | Integrity | Integritas | Kejujuran, etika profesional, transparansi, dan akuntabilitas dalam seluruh tindakan dan keputusan. |
| 2 | `innovation` | Innovation | Inovasi | Solusi baru yang kreatif, adaptif, dan berdaya guna melalui integrasi AI terdepan. |
| 3 | `collaboration` | Collaboration | Kolaborasi | Sinergi harmonis antara kecerdasan manusia (Human-in-the-Loop) dan otonomi agen AI. |
| 4 | `excellence` | Excellence | Keunggulan | Standar kualitas tertinggi dalam produk software, layanan teknis, dan operasional bisnis. |
| 5 | `adaptability` | Adaptability | Adaptabilitas | Tangguh dan responsif terhadap dinamika pasar teknologi dan kebutuhan industri. |
| 6 | `customer_centric` | Customer-Centric | Berorientasi pada Pelanggan | Kepuasan, keberhasilan, dan dampak nyata mitra serta pelanggan sebagai pusat inovasi. |
| 7 | `sustainability` | Sustainability | Keberlanjutan | Model bisnis dan tata kelola yang efisien secara biaya dan berjangka panjang. |

---

## 3. Governance Bodies & Decision Rules

Governance bodies represent collective or individual organs of authoritative corporate will:

### 3.1 Human Owner Authority (`GOV-OWNER`)
- **Mandate**: Ultimate strategic oversight, capital allocation, corporate restructuring, constitutional policy approvals, and final veto.
- **Decision Rule**: `sole_owner` (Individual Human Owner authorization).

### 3.2 Continuous Executive Board (`GOV-BOARD`)
- **Mandate**: Continuous executive governance, operational management, business growth strategies, resource coordination, and cross-division alignment.
- **Decision Rule**: `board_consensus` / `board_majority`.
- **Autonomy Boundary**: Empowered to act independently up to IDR 5.000.000 without requiring Owner sign-off for routine operational, marketing, or expansion spend.

### 3.3 Director AI Authority (`GOV-DIR-AI`)
- **Mandate**: Operational task coordination, workforce task decomposition, dispatching, and agent health monitoring.
- **Decision Rule**: `delegated_operational`.
- **Autonomy Boundary**: Routine operational actions and spend up to IDR 1.000.000 (DoA Level C).

---

## 4. Owner Directions: Advisory vs Binding

Owner instructions are formalized via `owner_directions` to maintain crystal-clear boundaries:

- **`advisory`**: Strategic advice, guidance, suggestions, or philosophical preferences. The Continuous Executive Board and Director AI evaluate advisory directions, incorporate them into sprint plans, or provide alternatives if operational constraints require adjustments.
- **`binding`**: Strict, mandatory governance directives, policy mandates, or capital orders. Must be formally acknowledged (`acknowledged_at`, `acknowledged_by`) and reflected in subsequent executive decisions.
- **Other Types**: `formal_approval` (DoA Level A sign-off), `strategic_instruction`, `delegation`, `clarification`.

---

## 5. Executive Decisions (`executive_decisions`)

Decisions made by the Continuous Executive Board are recorded with:
- Formal `decision_code` (`DEC-BOARD-YYYY-NNN`).
- Relationship to parent `direction_id` (if ratifying an Owner Direction).
- Category: `operational`, `business_growth`, `campaign`, `internal_policy`, `resource_allocation`, `restructuring`.
- Level: `A`, `B`, or `C`.
- Ratification status: `draft`, `ratified`, `in_execution`, `executed`, `archived`, `rescinded`.
