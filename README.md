# ⛏️ GPT33 — Cloud Mining Simulation & Token Yield Platform

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.x-777BB4?style=for-the-badge&logo=php" alt="PHP 8">
  <img src="https://img.shields.io/badge/MySQL-Database-4479A1?style=for-the-badge&logo=mysql" alt="MySQL">
  <img src="https://img.shields.io/badge/PWA-Enabled-5A0FC8?style=for-the-badge&logo=pwa" alt="PWA Ready">
  <img src="https://img.shields.io/badge/License-MIT-green?style=for-the-badge" alt="License">
</p>

---

## 📖 What is GPT33 Mining App?

**GPT33 Mining App** is a responsive, **full-stack Web and Progressive Web App (PWA)** built on **PHP & MySQL** that simulates cryptocurrency cloud mining operations, hardware lease contracts, and digital asset reward generation.

It combines an intuitive member mining interface with an automated background yield engine, multi-tier referral compensation trees, automated deposit gateways, and an administrative control panel for managing token economics.

---

## 🎯 Why GPT33? Problem & Solution

| The Challenge | How GPT33 Solves It |
| :--- | :--- |
| **Complex Real Mining Setup:** Physical crypto mining requires expensive ASIC hardware, continuous power maintenance, and high cooling costs. | Simulates cloud-based virtual mining contracts with customizable hashrates, predictable reward cycles, and zero hardware maintenance for users. |
| **Manual Yield Calculations:** Calculating daily mining rewards for thousands of active users manually creates accounting errors and server bottlenecks. | Executes optimized background cron routines (`cron/`) that automatically disburse rewards directly to user wallets based on contract parameters. |
| **Lack of Mobile Accessibility:** Crypto investors need real-time visibility into their mining earnings from their phones. | Built as an installable **PWA** with local service worker caching (`sw.js`) and app-like responsiveness. |

---

## 🌟 Key Features

### ⛏️ Member Cloud Mining Hub
- **Interactive Mining Rigs:** Choose from multiple mining tiers with variable hashrates, contract durations, and daily token yields.
- **Real-Time Earnings Tracker:** Live digital counters reflecting active mining outputs and accumulated balances.
- **Automated Deposit Channels:** Seamless fund deposits via crypto wallets and manual banking channels.
- **Withdrawal Processing:** Secure payout request system with address validation and status tracking.

### 🤝 Multi-Level Referral & Affiliate Engine
- **Hierarchical Referral Trees:** Automatically credits multi-tier commissions on downline investments and mining activities.
- **Team Performance Analytics:** Detailed breakdown of active downline miners, volume generated, and commissions paid.

### ⚙️ Automation & Background Workers
- **Precision Cron Engine:** Scheduled background worker scripts (`cron/`) that calculate daily token returns, compound earnings, and expire finished mining contracts.
- **RESTful API Architecture:** Modular backend endpoints (`api/`) powering interactive client-side operations.

### 🛡️ Administrative Command Suite
- **User & Wallet Oversight:** Review registered miners, adjust balances, and audit ledger transactions.
- **Mining Contract Customization:** Define contract pricing, hashrate tiers, minimum payout thresholds, and operational fees.
- **Deposit & Withdrawal Verification:** Approve or reject user transaction proofs with instant ledger synchronization.

---

## 🛠️ Technical Stack

- **Backend:** PHP 8.x (Modular MVC Architecture)
- **Database:** MySQL 5.7+ / MariaDB (Prepared Statements & Transactional Integrity)
- **Client Architecture:** Vanilla JS, Responsive CSS3, PWA Service Worker (`sw.js`)
- **API & Background:** RESTful JSON endpoints, Web & CLI Cron Jobs

---

## 📦 Local Installation & Setup

### 1. Requirements
- PHP 8.0 or higher with `pdo_mysql` and `curl` extensions enabled
- MySQL 5.7+ or MariaDB 10.3+
- Web server (Apache via XAMPP, Nginx, or Laragon)

### 2. Setup Database
1. Import `db_schema.sql` into your MySQL server:
   ```sql
   CREATE DATABASE gpt33_mining CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   USE gpt33_mining;
   SOURCE db_schema.sql;
   ```
2. Configure environment credentials in `.env`:
   ```env
   DB_HOST=localhost
   DB_NAME=gpt33_mining
   DB_USER=root
   DB_PASS=
   ```

### 3. Automated Cron Jobs
Configure server cron jobs to execute automated mining calculations:
```bash
# Calculate daily mining rewards at midnight
0 0 * * * php /path/to/GPT33/cron/daily_mining_payout.php >/dev/null 2>&1
```

---

## 👨‍💻 Author
**Rana Umair Nawaz**  
- [GitHub Profile](https://github.com/ranaumairnawaz)  
- [LinkedIn Profile](https://linkedin.com/in/ranaumairnawaz)
