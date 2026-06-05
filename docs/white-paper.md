# understandtech.app Technical White Paper v2.0

## TECHNICAL WHITE PAPER

understandtech.app

An AI-Powered, Community-First Certification Training Platform

Architectural Blueprint and Business Strategy

Version 2.0 — Deconstructed Edge-Native Architecture

Mirroring Skool.com community dynamics, integrating Loom.com video instruction, and delivering CompTIA CertMaster-equivalent certification preparation on a customized Moodle foundation with Cloudflare edge security and AI augmentation

A Product of

AI Tech Pros, Inc.

Prepared by:

Henry Jenkins, Chief Technology Officer

Nehemiah Harvard, Chief Executive Officer

Document Version 2.0

Confidential and Proprietary

## Executive Summary

understandtech.app is an AI-augmented certification training platform built on a customized Moodle foundation, designed to combine the community-first engagement model pioneered by Skool.com, the cinematic asynchronous video instruction format perfected by Loom.com, and the structured certification readiness methodology established by CompTIA CertMaster Learn. The platform will deliver instructor-led training paths for CompTIA Security+, Network+, A+, and additional certifications, with practicing professionals serving as the instructional voice rather than anonymous content libraries.

This white paper presents the technical architecture, AI integration strategy, business model, and execution roadmap for the platform. It is intended as the foundational reference document for engineering, instructional design, partnership development, and capital planning conversations through the platform's first eighteen months of operation.

#### What is New in Version 2.0

This revision incorporates five significant architectural improvements that transform the platform from a conventional Moodle deployment into a deconstructed edge-native monolith. The improvements isolate the database from the web compute, push video and AI workloads to Cloudflare's edge network, enforce Nginx and PHP-FPM as the asynchronous web engine, restructure the codebase as a lean plugin monorepo with zero-inbound CI/CD via GitHub Actions self-hosted runners, and externalize all LLM provider calls through a serverless Cloudflare AI Gateway Worker. The net effect is meaningfully better security posture, dramatically lower per-user infrastructure cost at scale, and a SOC 2 audit pathway that is supported by automated evidence collection rather than custom tooling. The full system architecture diagram in Section 2 visualizes the resulting topology.

#### Strategic Position

The certification training market is bifurcated. On one end, traditional providers (Pluralsight, LinkedIn Learning, CompTIA itself) deliver structured but impersonal content libraries. On the other end, creator-led communities on Skool deliver high engagement but lack certification rigor and credentialing depth. understandtech.app occupies the deliberate middle ground: certification-grade instructional rigor delivered through community-first engagement with named, accountable instructors who teach as a vocation rather than as content creators monetizing audiences.

The platform's defensibility comes from three reinforcing properties. First, instructional credibility from named experts with documented certification and professional experience teaching synchronously and asynchronously. Second, community velocity from a Skool-inspired engagement model that drives daily active use rather than passive enrollment. Third, AI augmentation that makes every student feel individually mentored even when the human instructor is offline. Each property reinforces the others, and the combination is what neither Skool nor CertMaster nor LinkedIn Learning currently offers.

#### Business Model

Tier

Price

Target Learner

Inclusions

Free Audit

$0

Curious prospects

Sample lessons, community read access, AI tutor demo

Single Certification

$49/mo or $399/yr

Individual cert candidates

One cert track, full labs, AI tutor, community

All Access

$99/mo or $899/yr

Career changers, multi-cert pursuers

All cert tracks, priority instructor time, portfolio

Cohort

$1,499 one-time

Structured 12-week learners

Live cohort, dedicated instructor pod, guaranteed certification readiness

#### Document Roadmap

Section 1 establishes the strategic positioning and competitive analysis. Section 2 presents the technical architecture, including the customized Moodle foundation, Loom-equivalent video infrastructure, CertMaster-equivalent confidence and readiness tracking, and Skool-equivalent community engagement layer. Section 3 details the AI integration strategy across tutoring, grading, content generation, and adaptive learning. Section 4 covers compliance, security, and instructor governance. Section 5 presents the eighteen-month execution roadmap with explicit decision gates. Section 6 addresses risk management and operational considerations.

## 1. Strategic Positioning and Competitive Analysis

### 1.1 The Three Reference Products

Three existing products define the conceptual perimeter of understandtech.app. Each is excellent within its own scope and limited beyond it. The platform's design synthesizes the strongest properties of each while addressing the gaps that prevent any single one from being the right answer for certification-focused adult learners.

#### Skool.com — Community-First Engagement

Skool succeeds because it removes friction from community engagement. The Classroom interface presents courses as visual card carousels with a clean two-pane lesson view. The Community feed sits alongside courses as a peer interaction layer. Points-based gamification with weekly, monthly, and all-time leaderboards creates ambient competition. Level progression gates access to advanced content, which transforms passive consumption into active progression. Member directories make the community feel populated rather than anonymous.

What Skool gets right is the integration. Comments reference lessons, leaderboard achievements appear in community feeds, and the same notification stream serves both course progress and social interaction. There is one inbox, one identity, one set of points. This integration is what produces the daily-active engagement that conventional LMS platforms cannot match.

What Skool lacks for the certification market: no structured certification mapping, no exam readiness measurement, no confidence-based mastery tracking, no hands-on lab integration, no enterprise compliance posture, and no credentialed instructional governance. Skool is excellent for creator-led communities and inadequate for certification preparation that requires defensible learning outcomes.

#### Loom.com — Cinematic Asynchronous Instruction

Loom transformed instructional video by making screen recording with face overlay frictionless. Instructors record once, embed everywhere, and the rendering quality and viewer experience match what previously required production studios. The viewer can adjust playback speed, comment at specific timestamps, react with emoji at moments of insight, and share clips that capture the most useful seconds of a longer video.

What Loom gets right is the production model. An instructor recording a Loom video has the cognitive load of a one-on-one conversation, not a lecture. The face overlay maintains parasocial connection. The timestamp commenting transforms videos into asynchronous discussions. The bandwidth and storage are absorbed by Loom rather than the instructor.

What Loom lacks for the certification market: no native LMS integration, no progress tracking against learning objectives, no quiz or assessment infrastructure, no certification mapping, and pricing that becomes prohibitive at scale for institutional deployments. Loom is excellent as a video creation tool and inadequate as a platform foundation.

#### CompTIA CertMaster Learn — Structured Certification Readiness

CertMaster Learn structures content around precise exam objectives. Every page maps to a specific objective in the SY0-701 (or equivalent) exam blueprint. After each practice question, students rate their confidence — Guessing, Unsure, Confident, or Certain — which combines with correctness to produce a four-quadrant mastery model. The combination of confident-correct (mastery), confident-incorrect (dangerous misconception), unsure-correct (lucky), and guessing-incorrect (unfamiliar) drives adaptive study recommendations that target dangerous misconceptions first.

What CertMaster gets right is the pedagogical rigor. Exam readiness percentages aggregate across domains weighted by CompTIA's published blueprint percentages. The radar chart visualization shows students exactly where they are strong and weak. Practice questions are calibrated to actual exam difficulty.

What CertMaster lacks: no community, no peer learning, no live instructor interaction, no hands-on lab infrastructure, and a content delivery experience that feels like 2015 corporate training. CertMaster is excellent as a study tool and inadequate as a learning environment that students choose to spend time in.

### 1.2 The understandtech.app Synthesis

The platform deliberately combines the strongest property of each reference product while eliminating the gaps. From Skool, it inherits the community-first engagement model, the points and leaderboard gamification, the clean two-pane lesson interface, and the unified identity across course and community. From Loom, it inherits the cinematic asynchronous video format with face overlay, timestamp commenting, and clip sharing. From CertMaster, it inherits the precise objective mapping, the four-level confidence rating model, the exam readiness measurement, and the adaptive study recommendations.

The synthesis adds three properties none of the reference products provide. AI augmentation throughout, from a 24/7 tutor that knows each student's progress to AI-assisted grading that gives instructors leverage over their time. Live cohort programming where named instructors teach synchronous twelve-week intensives. Hands-on lab integration that produces portfolio artifacts students can show employers.

#### Competitive Position Matrix

Capability

Skool

Loom

CertMaster

understandtech.app

Community engagement

Excellent

None

None

Skool-equivalent

Async video instruction

Basic

Excellent

Basic

Loom-equivalent

Certification objective mapping

None

None

Excellent

CertMaster-equivalent

Confidence and mastery tracking

None

None

Excellent

CertMaster-equivalent

AI tutoring

None

None

None

Native

AI grading and feedback

None

None

Limited

Native

Live cohort programming

Limited

None

None

Native

Hands-on labs and portfolio

None

None

Limited PBQs

Native

Named instructor presence

Creator-led

Self-record

None

Vocational

## 2. Technical Architecture

### 2.0 System Architecture Overview

The platform is deliberately structured as a deconstructed edge-native monolith. The application logic remains a single coherent Moodle codebase running on a single Linux virtual machine, which preserves the operational simplicity and credentialing depth that make Moodle the right choice for certification training. However, the workloads surrounding that monolith — content delivery, video streaming, AI inference, secrets management, database storage, and continuous deployment — are deliberately separated onto best-of-breed managed services. The result is a system where the failure of any one component does not cascade into platform-wide outage, where security responsibilities are pushed to the layer best equipped to handle them, and where infrastructure cost scales gracefully with usage rather than requiring up-front overprovisioning.

The full topology consists of three logical layers. The Edge and Security Layer runs entirely on Cloudflare and handles DNS, WAF, DDoS protection, SSL termination, CDN caching for static assets, signed video delivery via Cloudflare Stream, and a serverless AI Gateway Worker that brokers all LLM provider calls. The Compute Origin Layer runs on a single Azure Burstable virtual machine hosting Nginx, PHP-FPM 8.3, the Moodle codebase, a self-hosted GitHub Actions Runner, PgBouncer for database connection pooling, and OPcache for PHP bytecode acceleration. The Managed Data and DevOps Layer runs on Azure platform-as-a-service offerings (PostgreSQL Flexible Server, Cache for Redis, Files Premium SMB, Key Vault) along with external services for payments (Stripe), email (Postmark), real-time chat (Discord), live sessions (BigBlueButton), and ad-hoc video (Loom). The diagram below visualizes the relationships and data flows across all three layers.

Figure 1: understandtech.app System Architecture

#### Five Architectural Improvements That Define Version 2.0

Five specific architectural decisions distinguish this revision from the original v1.0 design. Each is implemented as a deliberate engineering choice rather than as a future aspiration.

1. Decoupled Database Tier. The primary PostgreSQL database moves off the application VM onto Azure Database for PostgreSQL Flexible Server on a Burstable B2s tier. This isolates database compute from web compute (preventing VACUUM and ANALYZE operations from starving web traffic of CPU cycles on burstable VMs), provides automated point-in-time recovery and backups that satisfy SOC 2 evidence requirements without custom tooling, and protects Moodle session state and gradebook integrity from VM rebuild events. PgBouncer runs locally on the application VM in transaction-pool mode to multiplex hundreds of PHP-FPM worker connections across the database's limited connection pool.

2. Edge-Driven Media Security. Core course video IP is hosted on Cloudflare Stream with signed JWT URLs generated on demand by Moodle's local_aitutor and theme integrations. The signing key lives in Azure Key Vault and is injected into PHP-FPM via environment variable rather than committed to source. Signed URLs expire in 60 seconds by default, preventing the URL-sharing piracy vector that plagues self-hosted video infrastructure. The video infrastructure carries zero server overhead because Cloudflare's edge handles encoding, HLS adaptive bitrate delivery, transcription, and global distribution.

3. High-Performance Asynchronous Web Engine. Nginx 1.26 with PHP-FPM 8.3 replaces Apache as the web server stack. The event-driven async model of Nginx supports an order of magnitude more concurrent connections per gigabyte of RAM than Apache's prefork model, which directly translates to the platform supporting cohort traffic spikes (30 students hitting Refresh simultaneously when a live session begins) without scaling the VM. PHP 8.3 with OPcache JIT compilation produces measurable performance gains for Moodle's gradebook calculations and quiz rendering. Unix domain sockets between Nginx and PHP-FPM reduce inter-process latency further.

4. Lean Plugin Monorepo with Self-Hosted Runner. Core Moodle codebase files are not tracked in the platform's GitHub repository. Only the platform's exclusive intellectual property (theme_understandtech, local_certmaster, local_aitutor, local_aigrading, mod_ctfflag, block_examreadiness, block_portfolio, and any core patches) lives in a clean Plugin Monorepo. A GitHub Actions Self-Hosted Runner runs as a systemd service on the Azure VM with outbound-only HTTPS connectivity to GitHub, eliminating the need for any inbound firewall holes from GitHub's IP ranges. The deployment workflow pulls changes, runs local CLI upgrades via Moodle's upgrade.php, and purges Redis application caches in under five seconds for typical plugin updates.

5. Serverless AI Gateway Worker. All synchronous LLM API calls are offloaded from the Moodle PHP layer to a serverless Cloudflare Worker that integrates with Cloudflare's AI Gateway product. The Worker handles API key obfuscation (Anthropic and OpenAI keys never appear in the Moodle codebase or runtime), prompt and response caching to cut LLM costs by 30 to 60 percent on repetitive queries, automatic fallback routing between Anthropic Claude as the primary provider and OpenAI GPT as the secondary, and Server-Sent Events streaming directly to the student's browser so PHP-FPM workers are never blocked waiting on LLM responses. Moodle generates a short-lived HMAC-signed JWT that the browser presents to the Worker for authentication.

### 2.1 Foundation Decision: Customized Moodle

The platform is built on Moodle 4.5 LTS as the learning management foundation, with custom plugins, themes, and external integrations layered on top to deliver the Skool-equivalent community experience and Loom-equivalent video instruction. The decision to build on Moodle rather than from scratch is deliberate and reflects the realities of timeline, credentialing, and operational risk for a focused certification training business.

Why Moodle for this venture specifically

Moodle is the world's most credentialed open-source LMS, used by universities, federal agencies, and certification academies globally. It ships with SCORM and xAPI support, accessibility certification, gradebook infrastructure, quiz banking, and a mature plugin ecosystem covering payments, gamification, analytics, and integrations. Building these features from scratch consumes twelve to eighteen months of engineering work that contributes nothing the business cannot get from Moodle. Customizing Moodle to feel modern and community-first is a three-to-four-month theme and plugin effort. The math favors customization for a focused certification training business with a near-term launch imperative.

Moodle does have limitations worth acknowledging. The default user interface feels dated. Multi-tenancy is weak compared to platforms built for SaaS from the ground up. The PHP codebase is less productive with modern AI coding tools than equivalent TypeScript or Python stacks. None of these limitations matter for a Phase 1 launch of a single-tenant certification training product. They become relevant only if the platform later evolves into a multi-tenant SaaS, at which point the architecture can be evolved deliberately rather than rebuilt under pressure.

#### Updated Configuration Baseline (Version 2.0)

Component

Choice

Rationale

Moodle version

4.5 LTS

Long-term support through 2027, current feature set

Operating system

Ubuntu Server 24.04 LTS

Five-year support window through April 2029

Web server

Nginx 1.26 + PHP-FPM 8.3

Async event loop, 6-10x concurrent capacity vs Apache (Improvement #3)

PHP runtime

PHP 8.3 with OPcache and JIT

Bytecode cache and JIT for Moodle quiz/gradebook performance

Application database

Azure PostgreSQL Flexible Server (B2s, PG 16)

Decoupled compute, automated PITR backups (Improvement #1)

DB connection pool

PgBouncer (transaction mode)

Multiplexes PHP-FPM workers across limited DB connection pool

In-memory cache

Azure Cache for Redis (Basic C0 → Std C1)

Session store DB1, app cache DB0, MUC lock factory

File storage

Azure Files Premium SMB

Shared moodledata volume with snapshot backups

Edge / DNS / WAF / SSL

Cloudflare Free tier

Unlimited DDoS, global CDN, managed WAF at $0/month

Origin protection

Cloudflare Authenticated Origin Pulls + Azure NSG

Origin accepts inbound only from Cloudflare IP ranges

Video delivery

Cloudflare Stream with signed JWTs

Edge-delivered HLS, 60s URL expiry (Improvement #2)

AI gateway

Cloudflare Worker + AI Gateway

Serverless LLM proxy with caching and fallback (Improvement #5)

Secrets management

Azure Key Vault + Managed Identity

Zero secrets in source, managed identity for VM access

CI/CD

GitHub Actions (cloud validate + self-hosted deploy)

Plugin monorepo, zero inbound firewall holes (Improvement #4)

Backup

Azure Backup (VM + Files) + native PG backups

30-day retention, SOC 2 evidence by default

Observability

Azure Monitor + App Insights + CF Analytics

Origin + edge metrics, unified audit log stream

### 2.2 The Skool-Equivalent Community Layer

Replicating Skool's community feel inside Moodle requires deliberate theme and plugin work. Out of the box, Moodle's forum module is functional but visually dated. The path to Skool-equivalent engagement combines three components: a custom Boost child theme that replaces Moodle's default navigation with a Skool-style four-tab top navigation (Community, Classroom, Calendar, Members), a Discord bridge for real-time chat that complements Moodle's asynchronous discussion forums, and the Level Up XP plugin configured to award points for forum participation, lesson completion, lab submissions, and quiz mastery.

#### Skool-Inspired Interface Design

Top navigation reduced to five items maximum (Community, Classroom, Calendar, Members, Leaderboards)

Classroom view as horizontal card carousel of certification tracks

Lesson view as two-pane layout: video left, lesson navigation right

Community feed as merged stream of forum posts, achievements, and announcements

Leaderboards displayed weekly, monthly, and all-time

Member profiles showing level, points, joined date, and current certification track

Notifications unified across course progress, community activity, and instructor responses

#### Gamification Configuration

Level Up XP is the canonical Moodle gamification plugin and supports custom event triggers that produce Skool-equivalent point dynamics. The XP economy is calibrated to drive specific behaviors: completing a lesson earns moderate XP, posting in the community earns smaller but frequent XP, helping another student by answering their forum question earns substantial XP, submitting a lab earns large XP, and passing a practice exam earns the largest XP rewards. Level progression unlocks access to advanced content, instructor office hours, and graduation badges that students display on their public profile.

### 2.3 The Loom-Equivalent Video Layer with Edge-Driven Security

Loom's value comes from frictionless recording, fast rendering, face overlay, and timestamp interaction. The platform replicates this experience using a dual approach: Cloudflare Stream as the primary infrastructure for core certification content (where the platform's intellectual property warrants edge-driven security), and Loom Team Plan for instructor ad-hoc recordings (where speed of production matters more than IP protection).

#### Architecture: Cloudflare Stream Primary, Loom Secondary

Core course videos are hosted on Cloudflare Stream from Phase 1 forward, not deferred to a later phase. The decision reflects Improvement #2 of the v2.0 architecture: the platform's most valuable intellectual property is the long-form instructional content produced by named instructors, and that content must be protected against the URL-sharing piracy vector that plagues self-hosted video infrastructure. Cloudflare Stream's signed JWT URL mechanism produces video access tokens that expire in 60 seconds by default, making URL sharing essentially impossible.

The token signing flow is straightforward to implement and adds negligible latency. When a student loads a lesson page, the Moodle PHP backend generates a JWT containing the video ID, expiration timestamp, and not-before claim, signs the JWT with HMAC-SHA256 using a signing key retrieved from Azure Key Vault at PHP-FPM startup, and returns the token to the lesson page's JavaScript via AJAX. The lesson page constructs the Stream player URL with the embedded token and renders the video. Total added latency from JWT generation: approximately 2 to 5 milliseconds, imperceptible to students. The signing key never appears in source control, never appears in the Moodle database, and is rotated quarterly through Key Vault's rotation policies.

Loom Team Plan remains the right tool for instructor ad-hoc recordings — quick walkthroughs, response videos, and the parasocial face-overlay format Loom popularized. Loom embeds drop cleanly into Moodle's text editor and require no custom integration. The economics are clean: Loom for instructor production (where speed matters), Stream for core IP (where security matters).

Video Use Case

Phase 1 (Launch)

Phase 2+

Core certification videos (IP)

Cloudflare Stream with signed JWT URLs

Continue Stream, expand player customization

Instructor ad-hoc recordings

Loom Team Plan, embed in lesson pages

Continue Loom for speed of production

Live cohort sessions

BigBlueButton integrated with Moodle

Continue BBB, add recording auto-publish to Stream

Short-form clips and reactions

Loom embeds

Native short video creation via Stream upload API

### 2.4 The CertMaster-Equivalent Certification Layer

CertMaster's exam readiness model is genuinely the strongest property of any product in the certification training market. Replicating it inside Moodle requires custom plugin development because no off-the-shelf Moodle plugin implements the confidence-rating-plus-objective-mapping methodology that makes CertMaster effective.

#### Custom Plugin: local_certmaster

A custom Moodle local plugin implements the certification readiness infrastructure. The plugin provides administrative tools to map every quiz question and content page to one or more certification objectives (SY0-701 for Security+, N10-009 for Network+, 220-1101 and 220-1102 for A+). A custom question behavior extends Moodle's quiz module to prompt for confidence ratings after each answer submission. Scheduled tasks recalculate mastery scores per objective after every quiz attempt. A dashboard block renders the domain radar chart and the overall exam readiness percentage.

#### Confidence Rating Model

Confidence

Correct

Diagnostic Interpretation

Adaptive Response

Certain

Yes

Mastery

Move to advanced material

Confident

Yes

Solid understanding

Continue current path

Unsure

Yes

Lucky guess, weak foundation

Reinforce concept

Guessing

Yes

Random luck, no learning

Schedule full re-teach

Certain

No

Dangerous misconception

Highest priority remediation

Confident

No

Active misunderstanding

High priority remediation

Unsure

No

Honest unfamiliarity

Add to study queue

Guessing

No

Acknowledged gap

Add to study queue

The dangerous misconception quadrant (Certain or Confident plus incorrect answer) is the highest-priority remediation target because students who are confidently wrong on the practice exam will be confidently wrong on the actual exam. This is the diagnostic insight that distinguishes CertMaster-style certification training from generic quiz-based learning.

#### Exam Readiness Calculation

Per-objective mastery scores aggregate using CompTIA's published exam blueprint percentages. For Security+ SY0-701, the blueprint allocates 22 percent to Threats Vulnerabilities and Mitigations, 25 percent to Security Architecture, 28 percent to Security Operations, 14 percent to Security Program Management, and 11 percent to General Security Concepts. Each domain mastery score multiplies by its blueprint weight to produce an overall readiness percentage. Students see this readiness percentage prominently on their dashboard alongside a radar chart showing per-domain mastery.

### 2.5 Hands-On Labs

Hands-on labs differentiate understandtech.app from purely cognitive certification training. Students who complete labs produce GitHub repositories, written incident reports, and documented investigations that serve as portfolio artifacts for employer conversations. The lab infrastructure varies by certification track.

Certification

Lab Infrastructure

Portfolio Output

Security+

Microsoft Sentinel and Defender in Azure tenant, vulnerable VMs

KQL hunt reports, incident response documentation

Network+

GNS3 or EVE-NG topology files, browser-based network simulator

Network design documents, troubleshooting writeups

A+

Browser-based VM environment with Windows and Linux clients

System administration logs, hardware troubleshooting documentation

Linux+

Per-student Linux containers via Kasm Workspaces

Shell script libraries, system configuration documentation

CySA+

Sentinel and Defender access, threat intel feeds

Threat hunt writeups, IOC analysis reports

#### Lab Integration Pattern

Labs integrate with Moodle through LTI 1.3, which is the educational technology standard for embedding external tools into LMS platforms. A lightweight lab gateway service handles LTI launches, provisions student access to the appropriate lab environment, and returns grades to Moodle's gradebook when students complete lab objectives. Flag submission for CTF-style lab progression uses a custom Moodle activity module that validates submitted flag values against expected answers and awards XP through Level Up.

## 3. AI Integration Strategy

Artificial intelligence is foundational to understandtech.app rather than a feature bolted onto a conventional LMS. Every student interaction is potentially AI-augmented in ways that improve learning outcomes without removing the human instructor's role. The integration strategy spans four primary capabilities: a 24/7 AI tutor, AI-assisted grading, AI-augmented content production, and adaptive learning path generation.

### 3.1 AI Tutor: 24/7 Mentor Without Giving Answers

The AI tutor is a sidebar widget present on every course and lab page. It receives context about the student's current activity, recent quiz performance, confidence ratings, and the relevant lesson content. It engages with students through natural conversation, helping them understand concepts, debug their thinking, and identify where to focus next.

Critical design constraint

The AI tutor must never reveal answers to assessment questions, lab flag values, or quiz solutions. Its purpose is to teach the underlying concepts so students can solve the assessments themselves. The system prompt enforces this constraint rigorously, with the model trained to redirect direct answer requests into Socratic dialogue that helps students discover answers through their own reasoning.

#### Tutor System Prompt Pattern

The tutor operates under a versioned system prompt that establishes its pedagogical philosophy, its no-answer constraint, and the specific behaviors it should and should not exhibit. The prompt includes example refusals for common bypass attempts, a hierarchy of helpful responses ranging from concept explanation to Socratic questioning to suggested next steps, and explicit instructions to acknowledge uncertainty rather than fabricate technical details.

The tutor is grounded in course content through retrieval-augmented generation. When a student asks a question, the tutor retrieves the most relevant lesson content, quiz questions, and reference documentation from a vector store, includes that retrieved content in the prompt to the language model, and generates a response that synthesizes the retrieved knowledge with the student's specific context. This grounding substantially reduces hallucination on factual technical topics.

#### Model Provider Strategy: Serverless AI Gateway Worker

The platform routes all LLM requests through a serverless Cloudflare Worker that integrates with Cloudflare's AI Gateway product. This is Improvement #5 in the v2.0 architecture, and it transforms how AI workloads interact with the Moodle origin in three important ways.

First, API key obfuscation. Anthropic and OpenAI API keys live exclusively in Cloudflare Workers secrets (not in Moodle's config.php, not in environment variables on the VM, not in the GitHub repository). The Moodle application never sees the upstream provider credentials. If the VM is ever compromised, the LLM API keys remain protected behind the edge. Key rotation happens in the Cloudflare dashboard without requiring a Moodle deployment.

Second, asynchronous streaming via Server-Sent Events. The student's browser opens an SSE connection directly to the Worker at ai.understandtech.app, passing a short-lived HMAC-signed JWT generated by Moodle for authentication. The Worker validates the JWT, retrieves the conversation context, calls the AI Gateway with the LLM request, and streams tokens back to the browser as they arrive from the LLM provider. The Moodle PHP-FPM workers are never held open waiting for a 10-to-30-second LLM response. Concurrency capacity stays high. PHP-FPM remains responsive for other student requests. Latency from question submission to first token of response: approximately 200-400 milliseconds.

Third, automatic provider routing and caching. Anthropic Claude is the primary provider because Claude's pedagogical conversation quality is meaningfully better for the patient, Socratic, encouragement-rich tone that effective tutoring requires. OpenAI GPT serves as the secondary provider for cost-sensitive bulk operations and as automatic fallback during Anthropic outages. Cloudflare's AI Gateway product handles the routing logic, retry behavior, and provider health checking. Cached responses to common questions (cached by RAG context hash, not by raw prompt to avoid serving wrong answers) cut LLM costs by 30 to 60 percent at the volumes typical of an active certification training community.

Per-student token consumption tracking, tier-based usage allowance enforcement, and audit logging of every AI interaction happen at the Worker level rather than in Moodle. The Worker writes audit records back to Moodle via authenticated webhook for compliance reporting, but the LLM provider relationships are encapsulated entirely at the edge.

### 3.2 AI-Assisted Grading

Written assessment grading is the largest time sink for human instructors in a certification training program. AI-assisted grading gives instructors leverage by producing recommended grades and detailed feedback that instructors review and approve, rather than grade from scratch. The grading workflow varies by stakes.

#### Grading Tiers

Submission Type

Stakes

Grading Mode

Instructor Role

Quiz free-text answer

Low

Fully automated

Spot check

Lab reflection paragraph

Low

Fully automated

Review flagged outliers

Incident report writeup

Medium

AI-recommended, instructor approves

Review and approve

Capstone project

High

AI-assisted, instructor grades

Primary grader, AI augments

Cohort final assessment

High

Instructor grades, AI provides second opinion

Authoritative grader

All grading decisions are logged with the submission content, the AI-recommended grade and feedback, any instructor override, and timestamps. This audit trail provides defensibility against grade disputes and accreditation scrutiny, and produces training data for ongoing model evaluation.

### 3.3 AI-Augmented Content Production

Content production is the largest fixed cost for any certification training platform. AI augmentation reduces the per-hour cost of producing quality content without replacing human subject matter expertise. The instructor remains the authoritative voice; AI accelerates the work surrounding the instruction.

#### Content Generation Use Cases

Quiz question drafts from lesson content, which instructors review and refine

Flashcard sets generated from terminology in lesson content

Practice scenario variants generated from base scenarios

Lesson summaries and key takeaway bullets

Transcripts and chapter markers for video lessons

Alt text for images and accessibility descriptions

Search-optimized lesson titles and descriptions

Email and notification copy variants for engagement testing

Every AI-generated content artifact passes through instructor review before publication. The platform does not auto-publish AI content to students because the educational outcomes depend on factual accuracy that requires human subject matter expert validation.

### 3.4 Adaptive Learning Paths

Adaptive learning combines the CertMaster-style mastery model with AI-generated study plan recommendations to produce a personalized study path for each student. The deterministic engine identifies weak objectives based on mastery scores. The AI layer translates those weak objectives into a specific recommended next session: which lesson to watch, which practice questions to attempt, which lab to schedule, and how long the session should take.

#### Adaptive Engine Operation

A scheduled task recalculates per-objective mastery scores hourly for active students. The task identifies the three weakest objectives weighted by exam blueprint importance. The AI layer receives the mastery profile, the student's recent activity history, the student's stated learning goals, and the available content library. The model returns a structured study plan with three to five activities, time estimates, and the rationale the student sees on their dashboard. Students can accept the plan, modify it, or request an alternative.

#### Exam Readiness Prediction

Exam readiness is initially calculated through the deterministic weighted-average formula across domain mastery scores. As the platform accumulates outcome data (students who passed or failed the actual certification exam, their readiness scores at exam time, and their lesson completion patterns), the readiness model can evolve into a predictive model that more accurately forecasts exam outcome. The transition from deterministic to predictive readiness occurs after the platform has accumulated outcome data from at least one hundred students who have sat for the certification exam.

## 4. Compliance, Security, and Instructor Governance

### 4.1 Data Protection and Privacy

The platform handles student personal information, payment data, learning records, and assessment submissions. The protection posture from launch is intentionally above what the regulatory environment strictly requires, because credibility with adult learners and future enterprise customers depends on visible security maturity.

#### Baseline Controls

TLS 1.3 for all network traffic with HSTS enforcement

Encryption at rest for all storage including database, file storage, and backups

Application-level encryption for sensitive fields including API keys and OAuth tokens

MFA required for instructor and administrator accounts

MFA optional but encouraged for student accounts

Audit logging of all access to student records and assessment data

Automated daily backups with 30-day retention

Point-in-time recovery for the database with 7-day window

#### Regulatory Alignment

The platform aligns with GDPR for international students, with student-initiated data export, correction, and deletion through self-service tools. CCPA equivalent rights for California students. PCI compliance scope is minimized by using Stripe as the payment processor, which keeps cardholder data out of the platform's systems entirely. The platform does not handle protected health information and does not require HIPAA compliance for its current scope.

SOC 2 Type I certification is a roadmap milestone for the second year of operation, gated on revenue justifying the audit investment of $30,000 to $80,000. Type II follows in the third year. These certifications are not required for individual learner enrollment but become necessary when pursuing enterprise customers or institutional partnerships.

### 4.2 Instructor Governance

The platform's instructional credibility depends on the quality and accountability of its instructors. Loose instructor governance produces inconsistent experiences and reputation damage. The platform implements explicit instructor governance from launch.

#### Instructor Qualification Standards

Active certification in the subject they teach (current Security+ for Security+ instructors)

Minimum three years of professional experience in the relevant technical domain

Documented instructional experience or completion of platform instructor training

Background check appropriate to the instructor role

Signed instructor agreement covering content standards, student conduct, and compensation

#### Content Quality Process

Every piece of instructional content goes through a documented review process before publication. The original instructor creates the content. A second qualified instructor reviews for technical accuracy. The instructional design lead reviews for pedagogical effectiveness. Content is published with the originating instructor's name attached and a publication date. Updates to content are versioned with change logs visible to students who completed prior versions.

#### Student Code of Conduct

Students agree to a published code of conduct covering respectful community behavior, academic integrity, and the platform's policies on AI tutor use. The code explicitly addresses certification exam preparation expectations, including that students must not share actual exam content (which would violate CompTIA's non-disclosure agreement) and that the platform's practice questions are designed to develop competency rather than memorize specific exam questions.

### 4.3 Accessibility

The platform commits to WCAG 2.1 Level AA conformance as a baseline accessibility standard. All video content includes accurate captions. All interactive elements support keyboard navigation. Color contrast meets minimum ratios. Screen reader compatibility is tested with each major release. Accessibility is verified through both automated testing in CI and periodic manual audits.

Accessibility is not only a regulatory consideration. The certification training market includes substantial populations of learners with disabilities pursuing career changes, and the platform's posture on accessibility directly affects market reach. Veterans pursuing post-service careers (some with service-connected disabilities) are a specific population the platform serves well, particularly given AI Tech Pros' SDVOSB status.

## 5. Execution Roadmap

The platform launches in deliberate phases that produce revenue and learning at each step rather than postponing customer contact until a perfected product exists. The roadmap spans eighteen months from inception to mature multi-track operation.

### Phase 1: Foundation and Security+ Launch (Months 1-4)

#### Goal

Ship a customized Moodle instance with the Skool-inspired theme, Loom-based video instruction, the CertMaster-equivalent confidence and readiness tracking for Security+ SY0-701, and the AI tutor in beta. Launch to a private beta cohort of 20 to 30 students at introductory pricing.

#### Engineering Workstreams

Provision production and staging Moodle infrastructure on Azure

Develop the custom Boost child theme matching the understandtech.app brand

Install and configure Level Up XP, Stripe payment plugin, and Configurable Reports

Develop the local_certmaster plugin with SY0-701 objective mapping and confidence tracking

Build the AI tutor sidecar service consuming the LLM Gateway

Integrate Loom embeds in lesson templates with progress tracking

Stand up Discord server with Moodle SSO for real-time community

#### Content Workstreams

Migrate or create 80-100 lessons covering all five SY0-701 domains

Develop 400-question quiz bank with confidence rating prompts

Build three full-length practice exams

Record introductory video for each lesson using Loom

Create the first three hands-on labs with portfolio outputs

### Phase 2: Public Launch and Multi-Certification Expansion (Months 5-10)

#### Goal

Public launch at full pricing. Expand from Security+ to include Network+ and A+. Launch the cohort program with the first instructor-led twelve-week intensive. Begin building the second instructor pod.

#### Workstreams

Public marketing site launch with conversion optimization

Network+ certification track build (N10-009 objective mapping, 80-100 lessons, 400-question bank)

A+ certification track build (220-1101 and 220-1102 split into Core 1 and Core 2)

Cohort program infrastructure: scheduled live sessions, dedicated instructor pod, graduation tracking

Expanded lab library: Network+ labs in GNS3/EVE-NG, A+ labs in browser-based VMs

AI grading service for incident reports and lab reflections

Second instructor recruitment, qualification, and onboarding

### Phase 3: Scale and Compliance (Months 11-18)

#### Goal

Scale to 500 to 1000 active students across all certification tracks. Add CySA+ and Linux+ to the certification library. Pursue SOC 2 Type I certification. Develop institutional partnerships for B2B sales channel.

#### Workstreams

CySA+ certification track build (CS0-003 objective mapping)

Linux+ certification track build (XK0-005 objective mapping)

SOC 2 Type I audit preparation and audit execution

VET TEC application for VA-funded veterans education benefits

B2B sales materials and reseller program design

Third and fourth instructor pods built out for capacity

Adaptive learning engine with predictive exam readiness

Custom video infrastructure migration for core content

### Phase 4: Optionality and Maturity (Months 19+)

By month 18, the platform supports five certification tracks, a stable cohort program, SOC 2 Type I certification, and 500 to 1000 active students. Phase 4 decisions depend on the trajectory: continued independent operation, pursuit of enterprise contracts, expansion into additional certifications (CISSP, AWS, Azure cloud certifications), platform multi-tenancy work to support reseller channel, or strategic conversations with potential acquirers. The architecture supports all paths because the foundation does not lock the business into a single trajectory.

## 6. Risk Management and Operational Considerations

Risk

Severity

Mitigation

Content production capacity insufficient to meet roadmap

High

Reuse existing instructional material, contracted SMEs for content drafts, AI-assisted content generation with human review, prioritize Security+ depth over Network+ breadth in Phase 1

Single instructor dependency damages credibility

High

Recruit second qualified instructor in Phase 2, develop instructor pod model with two qualified instructors per certification track

AI tutor leaks assessment answers despite constraints

Medium

Adversarial red-team testing of system prompts, conversation logging with periodic review, instructor escalation for problematic conversations

Moodle UI feels dated despite theme work

Medium

Aggressive Boost child theme customization in Phase 1, plan transition to custom frontend in Phase 4 if multi-tenancy or sale motivates investment

Loom pricing becomes prohibitive at scale

Low

Phase 2 transition to owned video infrastructure for core content, retain Loom for ad hoc instructor recordings

Stripe processing rate exceeds margins on cohort pricing

Low

Cohort pricing structured as single payments to avoid recurring processing fees, ACH option for annual subscriptions

Compliance audit failures block enterprise channel

Medium

SOC 2 Type I in Phase 3 with Drata or Vanta tooling, instructor governance documented from Phase 1, audit trail infrastructure from Phase 1

Founder bandwidth conflict between teaching and operating

High

Build instructor pod to two qualified instructors per track by month 8, automate operational tasks aggressively, reserve founder time for high-leverage work

CompTIA challenges practice question authenticity

Medium

Original practice questions written by instructors, never copied from actual exams, content review process documents originality

### 6.1 Operational Considerations

#### Hosting and Infrastructure

Production Moodle runs on Azure VMs (Ubuntu 24.04 LTS) with Azure Database for PostgreSQL and Azure Files for shared storage. Estimated monthly infrastructure cost at launch is $200 to $400, scaling to $800 to $1500 at 500 active students. Cloudflare in front provides CDN, DDoS protection, WAF, and SSL at no recurring cost on the free tier. Backup and disaster recovery use Azure native snapshot tools with 30-day retention.

#### AI Service Costs

Anthropic Claude API costs scale with student usage. At typical tutoring volumes for an active certification training community, expect approximately $0.50 to $2.00 per active student per month in LLM costs. This cost is recovered through subscription pricing with healthy margin. Per-tenant cost tracking through the LLM Gateway enables per-student attribution and usage-based billing for future enterprise tiers.

#### Support Model

Self-service support through documentation, AI tutor, and community discussion handles the majority of student questions. Email support through a help desk (HelpScout, Front, or similar) with 24-hour response SLA for paid tiers. Live chat for cohort students during instructor office hours. The community itself becomes a substantial support channel as the population grows and senior students help newer ones.

#### Marketing Channels

Initial marketing channels favor content marketing and community-led growth over paid acquisition. Technical blog posts ranking for certification preparation searches. YouTube videos demonstrating instructional quality. Veteran community partnerships through veteran service organizations. Reddit and LinkedIn presence by instructors building personal brand. Paid acquisition through Google and Meta ads is tested in Phase 2 once organic conversion rates are measured.

## Appendix A: Technology Stack Reference (Version 2.0)

The complete technology inventory reflecting the deconstructed edge-native architecture. Each row identifies whether the component is part of the Edge and Security Layer (Cloudflare), the Compute Origin (Azure VM), or the Managed Data and DevOps layer (Azure PaaS plus external services).

Layer

Component

Version / Tier

Purpose

Edge

Cloudflare DNS / WAF / CDN

Free tier

Authoritative DNS, DDoS, WAF, SSL termination, static asset CDN

Edge

Cloudflare Stream

Pay-per-use

Signed JWT video delivery (Improvement #2)

Edge

Cloudflare Workers + AI Gateway

Workers Paid $5/mo

Serverless LLM proxy with caching and fallback (Improvement #5)

Edge

Cloudflare Workers KV

Included

Prompt response cache, rate limit state

Edge

Authenticated Origin Pulls

Free

Cert-pinned origin lockdown

Origin OS

Ubuntu Server

24.04 LTS

Five-year support through April 2029

Origin Web

Nginx

1.26 stable

Async event-driven web server (Improvement #3)

Origin Runtime

PHP-FPM

8.3 with OPcache + JIT

PHP execution, bytecode cache, JIT compilation

Origin LMS

Moodle

4.5 LTS

Course delivery, gradebook, quiz infrastructure

Origin DB Pool

PgBouncer

Latest stable

Transaction-mode connection multiplexing

Origin CI/CD

GitHub Actions Self-Hosted Runner

Latest

Outbound-only deployment (Improvement #4)

Data

Azure Database for PostgreSQL Flexible Server

Burstable B2s, PG 16

Decoupled primary database (Improvement #1)

Data

Azure Cache for Redis

Basic C0 → Standard C1

Sessions, MUC application cache, lock factory

Data

Azure Files Premium SMB

Premium tier

moodledata shared volume

Data

Azure Key Vault

Standard tier

Secrets, certificates, signing keys, managed identity integration

Data

Azure Backup

Standard

Automated VM and Files snapshot backup, 30-day retention

AI

Anthropic Claude API

Latest model

Primary LLM (routed through AI Gateway)

AI

OpenAI GPT API

Latest model

Secondary LLM, fallback (routed through AI Gateway)

AI

pgvector (in Postgres)

16.x

RAG embeddings for AI tutor context retrieval

Community

Discord

Free + Nitro

Real-time community chat with OIDC SSO from Moodle

Community

Moodle Forums (native)

Bundled

Asynchronous discussion, course Q&amp;amp;A

Video

Cloudflare Stream

Pay-per-use

Core IP videos with signed JWTs (Phase 1 forward)

Video

Loom Team Plan

$15/user/mo

Instructor ad-hoc recordings

Live Sessions

BigBlueButton

Self-hosted or BBB Cloud

Cohort live sessions, recording pipeline

Payments

Stripe

Standard

Subscriptions, webhooks, PCI scope offload

Email

Postmark

Starter

Transactional email, notifications

Observability

Azure Monitor + Application Insights

Standard tier

Origin metrics, traces, logs

Observability

Cloudflare Analytics

Free + Logpush

Edge metrics, WAF events, AI Gateway telemetry

Compliance

Vanta or Drata

Starter

SOC 2 evidence collection and continuous monitoring

## Appendix B: Moodle Plugin Inventory

Plugin

Type

Purpose

theme_understandtech

Theme (custom)

Skool-inspired UI matching brand

local_certmaster

Local plugin (custom)

Objective mapping, confidence tracking, exam readiness

local_aitutor

Local plugin (custom)

AI tutor sidebar and conversation persistence

local_aigrading

Local plugin (custom)

AI grading workflow with instructor approval

mod_ctfflag

Activity module (custom)

CTF-style flag submission for labs

block_examreadiness

Block (custom)

Dashboard radar chart and readiness percentage

block_portfolio

Block (custom)

Auto-generated student portfolio

block_xp

Block (Level Up XP)

Gamification, points, levels, leaderboards

paygw_stripe

Payment gateway

Stripe payment processing

enrol_stripepayment

Enrollment

Subscription-based enrollment

filter_h5p

Filter

Interactive H5P content

mod_h5pactivity

Activity module

H5P interactive activities

auth_oidc

Authentication

OIDC SSO for Discord bridge

mod_bigbluebuttonbn

Activity module

Live cohort sessions via BBB

report_configurablereports

Report

Custom admin and instructor reports

## Appendix C: Glossary

CompTIA SY0-701 — The current Security+ exam version with five domains covering general security concepts, threats and vulnerabilities, security architecture, security operations, and security program management.

CompTIA N10-009 — The current Network+ exam version covering networking fundamentals, implementation, operations, security, and troubleshooting.

CompTIA 220-1101 / 220-1102 — The current A+ exam pair, with Core 1 covering hardware and Core 2 covering operating systems and security.

LTI 1.3 — Learning Tools Interoperability version 1.3, the IMS Global standard for embedding external educational tools into LMS platforms with secure authentication and grade return.

RAG (Retrieval-Augmented Generation) — An AI pattern that retrieves relevant context from a knowledge base and includes it in the prompt to a language model, reducing hallucination on factual topics.

SCORM / xAPI — Educational content interchange standards. SCORM is older and widely supported. xAPI (Experience API, formerly Tin Can) is more modern and captures richer learning interactions.

SDVOSB — Service-Disabled Veteran-Owned Small Business. A Small Business Administration certification that qualifies companies for set-aside federal contracts and other preferences.

SOC 2 — System and Organization Controls 2, an AICPA framework for security, availability, processing integrity, confidentiality, and privacy controls. Type I attests controls are designed correctly. Type II attests controls operate effectively over a sustained period.

VET TEC — Veteran Employment Through Technology Education Courses, a VA program that funds qualified veterans to attend approved technology training programs.

This white paper is a working architectural and strategic document for the understandtech.app platform. It should be revisited quarterly and updated as market conditions, customer feedback, and platform evolution warrant.

understandtech.app — A Product of AI Tech Pros, Inc. — Confidential and Proprietary
