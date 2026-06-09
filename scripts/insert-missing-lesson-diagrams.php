<?php
/**
 * Insert infographic blocks into SEC701 lessons that have zero diagrams.
 *
 * @copyright 2026 AI Tech Pros, Inc.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$lessonsdir = dirname(__DIR__) . '/content/security-plus/lessons';

$insertions = [
    'sy701_2_4.html' => [
        'anchor' => "<h4>Malware Attacks</h4>\n<ul>",
        'block' => <<<'HTML'
<h4>Malware Attacks</h4>
<h4>Visual Representation: Malware Types and Indicators</h4>
<p>The following diagram categorizes common malware families and typical indicators of compromise.</p>
<div class="ut-lesson-diagram">
<div class="diagram-title">🦠 Malware Families and Indicators</div>
<div class="malware-grid">
<div class="malware-card">
<h4>🔒 Ransomware</h4>
<ul>
<li>Encrypted files and ransom notes</li>
<li>Unusual outbound traffic to C2</li>
<li>Mass file extension changes</li>
</ul>
</div>
<div class="malware-card">
<h4>🐴 Trojan</h4>
<ul>
<li>Backdoor network connections</li>
<li>Unexpected privileged processes</li>
<li>Data exfiltration patterns</li>
</ul>
</div>
<div class="malware-card">
<h4>🪱 Worm</h4>
<ul>
<li>Rapid lateral movement</li>
<li>High bandwidth consumption</li>
<li>Repeated infection of hosts</li>
</ul>
</div>
<div class="malware-card">
<h4>👁️ Spyware / Keylogger</h4>
<ul>
<li>Keystroke capture artifacts</li>
<li>Hidden monitoring processes</li>
<li>Credential theft attempts</li>
</ul>
</div>
</div>
</div>
<ul>
HTML,
    ],
    'sy701_2_5.html' => [
        'anchor' => "<h4>Mitigation Techniques</h4>\n<ul>",
        'block' => <<<'HTML'
<h4>Mitigation Techniques</h4>
<h4>Visual Representation: Enterprise Mitigation Controls</h4>
<p>The following diagram maps core mitigation techniques to their security purpose.</p>
<div class="ut-lesson-diagram">
<div class="diagram-title">🛡️ Enterprise Mitigation Techniques</div>
<div class="concept-grid">
<div class="concept-item">
<strong>Segmentation</strong><br>
Limit lateral movement with VLANs, firewalls, and micro-segmentation.
</div>
<div class="concept-item">
<strong>Access Control</strong><br>
ACLs, RBAC, and least privilege reduce unauthorized access.
</div>
<div class="concept-item">
<strong>Allow Listing</strong><br>
Only approved applications execute; blocks unknown malware.
</div>
<div class="concept-item">
<strong>Patching</strong><br>
Timely updates close known vulnerability paths.
</div>
<div class="concept-item">
<strong>Encryption</strong><br>
Protects data at rest, in transit, and during processing.
</div>
<div class="concept-item">
<strong>Monitoring</strong><br>
SIEM, EDR, and logging enable early detection and response.
</div>
</div>
</div>
<ul>
HTML,
    ],
    'sy701_3_4.html' => [
        'anchor' => "<h4>High Availability</h4>\n<ul>",
        'block' => <<<'HTML'
<h4>High Availability</h4>
<h4>Visual Representation: Resilience and Recovery Architecture</h4>
<p>The following diagram illustrates continuity tiers and recovery objectives.</p>
<div class="ut-lesson-diagram">
<div class="diagram-title">🔄 Resilience, Availability, and Recovery</div>
<div class="flow-diagram">
<div class="flow-step">
<strong>High Availability</strong><br>
Load balancing<br>
Clustering / failover<br>
Redundant components
</div>
<div class="flow-arrow" aria-hidden="true">→</div>
<div class="flow-step">
<strong>Site Recovery</strong><br>
Hot / warm / cold sites<br>
Geographic dispersion<br>
COOP planning
</div>
<div class="flow-arrow" aria-hidden="true">→</div>
<div class="flow-step">
<strong>Recovery Objectives</strong><br>
RTO — time to restore<br>
RPO — acceptable data loss<br>
MTTR / MTBF metrics
</div>
</div>
</div>
<ul>
HTML,
    ],
    'sy701_4_1.html' => [
        'anchor' => "<h4>Secure Baselines</h4>\n<ul>",
        'block' => <<<'HTML'
<h4>Secure Baselines</h4>
<h4>Visual Representation: Secure Baseline Lifecycle</h4>
<p>The following diagram shows how organizations establish, deploy, and maintain secure baselines.</p>
<div class="ut-lesson-diagram">
<div class="diagram-title">📐 Secure Baseline Lifecycle</div>
<div class="flow-diagram">
<div class="flow-step">
<strong>1. Establish</strong><br>
Define hardened configs<br>
Align to policy &amp; CIS benchmarks
</div>
<div class="flow-arrow" aria-hidden="true">→</div>
<div class="flow-step">
<strong>2. Deploy</strong><br>
Automate rollout<br>
Image / MDM / IaC pipelines
</div>
<div class="flow-arrow" aria-hidden="true">→</div>
<div class="flow-step">
<strong>3. Maintain</strong><br>
Continuous compliance scans<br>
Patch &amp; drift remediation
</div>
</div>
</div>
<ul>
HTML,
    ],
    'sy701_4_2.html' => [
        'anchor' => "<h4>Asset Management Processes</h4>\n<ul>",
        'block' => <<<'HTML'
<h4>Asset Management Processes</h4>
<h4>Visual Representation: Asset Lifecycle Security</h4>
<p>The following diagram maps security touchpoints across the asset lifecycle.</p>
<div class="ut-lesson-diagram">
<div class="diagram-title">📦 Hardware, Software, and Data Asset Lifecycle</div>
<div class="concept-grid">
<div class="concept-item">
<strong>Acquisition</strong><br>
Security requirements in procurement; approved vendors only.
</div>
<div class="concept-item">
<strong>Assignment</strong><br>
Ownership, classification, and accountability tracking.
</div>
<div class="concept-item">
<strong>Monitoring</strong><br>
Inventory, enumeration, and unauthorized device detection.
</div>
<div class="concept-item">
<strong>Disposal</strong><br>
Sanitization, destruction, and certificate of destruction.
</div>
</div>
</div>
<ul>
HTML,
    ],
    'sy701_4_3.html' => [
        'anchor' => "<h4>Identification Methods</h4>\n<ul>",
        'block' => null, // filled below after reading file
    ],
];

// sy701_4_3 anchor may differ - read file
$path43 = $lessonsdir . '/sy701_4_3.html';
if (is_readable($path43)) {
    $h43 = file_get_contents($path43);
    if (preg_match('/<h4>([^<]+)<\/h4>\s*\n<ul>/', $h43, $m)) {
        $h4 = $m[1];
        $insertions['sy701_4_3.html'] = [
            'anchor' => "<h4>{$h4}</h4>\n<ul>",
            'block' => <<<HTML
<h4>{$h4}</h4>
<h4>Visual Representation: Vulnerability Management Phases</h4>
<p>The following diagram outlines the continuous vulnerability management cycle.</p>
<div class="ut-lesson-diagram">
<div class="diagram-title">🔍 Vulnerability Management Cycle</div>
<div class="flow-diagram">
<div class="flow-step">
<strong>Identify</strong><br>
Scanning, pen tests, threat intel
</div>
<div class="flow-arrow" aria-hidden="true">→</div>
<div class="flow-step">
<strong>Prioritize</strong><br>
Risk scoring, exploitability, exposure
</div>
<div class="flow-arrow" aria-hidden="true">→</div>
<div class="flow-step">
<strong>Remediate</strong><br>
Patch, mitigate, accept, transfer
</div>
<div class="flow-arrow" aria-hidden="true">→</div>
<div class="flow-step">
<strong>Verify</strong><br>
Re-scan, validate fixes, report
</div>
</div>
</div>
<ul>
HTML,
        ];
    }
}

$insertions['sy701_4_4.html'] = [
    'anchor' => "<h4>Monitoring Computing Resources</h4>\n<ul>",
    'block' => <<<'HTML'
<h4>Monitoring Computing Resources</h4>
<h4>Visual Representation: Security Monitoring Stack</h4>
<p>The following diagram maps alerting and monitoring layers across the enterprise.</p>
<div class="ut-lesson-diagram">
<div class="diagram-title">📡 Security Monitoring and Alerting</div>
<div class="concept-grid">
<div class="concept-item">
<strong>Log Aggregation</strong><br>
Centralize syslog, app, and audit events in a SIEM.
</div>
<div class="concept-item">
<strong>Network Monitoring</strong><br>
IDS/IPS, flow analysis, and NDR for anomaly detection.
</div>
<div class="concept-item">
<strong>Endpoint Telemetry</strong><br>
EDR agents capture process, file, and registry activity.
</div>
<div class="concept-item">
<strong>Alert Triage</strong><br>
Correlation rules, SOAR playbooks, and analyst escalation.
</div>
</div>
</div>
<ul>
HTML,
];

$insertions['sy701_4_5.html'] = [
    'anchor' => "<h4>Security Enhancement Technologies</h4>\n<ul>",
    'block' => null,
];
$path45 = $lessonsdir . '/sy701_4_5.html';
if (is_readable($path45) && preg_match('/<h4>([^<]+)<\/h4>\s*\n<ul>/', file_get_contents($path45), $m)) {
    $h4 = $m[1];
    $insertions['sy701_4_5.html'] = [
        'anchor' => "<h4>{$h4}</h4>\n<ul>",
        'block' => <<<HTML
<h4>{$h4}</h4>
<h4>Visual Representation: Security Capability Enhancement</h4>
<p>The following diagram shows how enterprises layer capabilities to improve detection and response.</p>
<div class="ut-lesson-diagram">
<div class="diagram-title">⚡ Security Enhancement Stack</div>
<div class="flow-diagram">
<div class="flow-step">
<strong>Visibility</strong><br>
Logging, asset inventory, baselines
</div>
<div class="flow-arrow" aria-hidden="true">→</div>
<div class="flow-step">
<strong>Detection</strong><br>
SIEM, EDR, UEBA, threat intel feeds
</div>
<div class="flow-arrow" aria-hidden="true">→</div>
<div class="flow-step">
<strong>Response</strong><br>
SOAR, IR playbooks, automated containment
</div>
</div>
</div>
<ul>
HTML,
    ];
}

$insertions['sy701_4_6.html'] = [
    'anchor' => "<h4>Identity and Access Management</h4>\n<ul>",
    'block' => <<<'HTML'
<h4>Identity and Access Management</h4>
<h4>Visual Representation: IAM Lifecycle</h4>
<p>The following diagram illustrates identity provisioning through de-provisioning and access enforcement.</p>
<div class="ut-lesson-diagram ut-infographic">
<div class="diagram-title">🔐 Identity and Access Management Lifecycle</div>
<figure class="ut-svg-figure" aria-label="IAM lifecycle: provision, authenticate, authorize, audit, deprovision">
<svg class="ut-cia-triangle" viewBox="0 0 480 120" xmlns="http://www.w3.org/2000/svg" role="img">
<defs>
<linearGradient id="iamFlowGrad" x1="0%" y1="0%" x2="100%" y2="0%">
<stop offset="0%" stop-color="#1A8A7D" stop-opacity="0.5"/>
<stop offset="100%" stop-color="#C9A227" stop-opacity="0.35"/>
</linearGradient>
</defs>
<rect x="10" y="40" width="460" height="8" rx="4" fill="url(#iamFlowGrad)"/>
<circle cx="50" cy="44" r="28" fill="#0B1F3A" stroke="#C9A227" stroke-width="2"/>
<text x="50" y="48" text-anchor="middle" fill="#f8fafc" font-size="9">Provision</text>
<circle cx="146" cy="44" r="28" fill="#0B1F3A" stroke="#C9A227" stroke-width="2"/>
<text x="146" y="48" text-anchor="middle" fill="#f8fafc" font-size="9">Authenticate</text>
<circle cx="242" cy="44" r="28" fill="#0B1F3A" stroke="#C9A227" stroke-width="2"/>
<text x="242" y="48" text-anchor="middle" fill="#f8fafc" font-size="9">Authorize</text>
<circle cx="338" cy="44" r="28" fill="#0B1F3A" stroke="#C9A227" stroke-width="2"/>
<text x="338" y="48" text-anchor="middle" fill="#f8fafc" font-size="9">Audit</text>
<circle cx="434" cy="44" r="28" fill="#0B1F3A" stroke="#C9A227" stroke-width="2"/>
<text x="434" y="48" text-anchor="middle" fill="#f8fafc" font-size="9">Deprovision</text>
</svg>
</figure>
<div class="flow-diagram">
<div class="flow-step">
<strong>Federation / SSO</strong><br>
SAML, OAuth, LDAP
</div>
<div class="flow-arrow" aria-hidden="true">→</div>
<div class="flow-step">
<strong>MFA</strong><br>
Biometrics, tokens, FIDO2
</div>
<div class="flow-arrow" aria-hidden="true">→</div>
<div class="flow-step">
<strong>Access Models</strong><br>
RBAC, ABAC, least privilege
</div>
</div>
</div>
<ul>
HTML,
];

$insertions['sy701_4_7.html'] = [
    'anchor' => "<h4>Use Cases of Automation and Scripting</h4>\n<ul>",
    'block' => null,
];
$path47 = $lessonsdir . '/sy701_4_7.html';
if (is_readable($path47) && preg_match('/<h4>([^<]+)<\/h4>\s*\n<ul>/', file_get_contents($path47), $m)) {
    $h4 = $m[1];
    $insertions['sy701_4_7.html'] = [
        'anchor' => "<h4>{$h4}</h4>\n<ul>",
        'block' => <<<HTML
<h4>{$h4}</h4>
<h4>Visual Representation: Security Automation Use Cases</h4>
<p>The following diagram highlights common automation and orchestration scenarios in secure operations.</p>
<div class="ut-lesson-diagram">
<div class="diagram-title">🤖 Automation and Orchestration Use Cases</div>
<div class="concept-grid">
<div class="concept-item">
<strong>Provisioning</strong><br>
Automated account creation, RBAC assignment, and MFA enrollment.
</div>
<div class="concept-item">
<strong>Patch Orchestration</strong><br>
Scan → approve → deploy → verify across fleets.
</div>
<div class="concept-item">
<strong>Incident Response</strong><br>
SOAR playbooks for isolate, collect evidence, and notify.
</div>
<div class="concept-item">
<strong>Compliance Reporting</strong><br>
Scheduled control checks and attestation evidence collection.
</div>
</div>
</div>
<ul>
HTML,
    ];
}

$insertions['sy701_5_5.html'] = [
    'anchor' => "<h4>Audit and Assessment Types</h4>\n<ul>",
    'block' => <<<'HTML'
<h4>Audit and Assessment Types</h4>
<h4>Visual Representation: Audits and Assessments Landscape</h4>
<p>The following diagram compares internal, external, and technical assessment types.</p>
<div class="ut-lesson-diagram">
<div class="diagram-title">📋 Audit and Assessment Types</div>
<div class="concept-grid">
<div class="concept-item">
<strong>Internal Audit</strong><br>
Compliance, self-assessment, audit committee oversight.
</div>
<div class="concept-item">
<strong>External Audit</strong><br>
Regulatory exams, independent third-party attestation.
</div>
<div class="concept-item">
<strong>Penetration Testing</strong><br>
Physical, offensive, defensive, and integrated campaigns.
</div>
<div class="concept-item">
<strong>Continuous Assessment</strong><br>
Ongoing control monitoring vs. point-in-time reviews.
</div>
</div>
</div>
<ul>
HTML,
];

$insertions['sy701_5_6.html'] = [
    'anchor' => "<h4>Security Awareness</h4>\n<ul>",
    'block' => <<<'HTML'
<h4>Security Awareness</h4>
<h4>Visual Representation: Security Awareness Program</h4>
<p>The following diagram outlines a continuous awareness training cycle for employees.</p>
<div class="ut-lesson-diagram">
<div class="diagram-title">🎓 Security Awareness Program Cycle</div>
<div class="flow-diagram">
<div class="flow-step">
<strong>Train</strong><br>
Policies, phishing, OpSec, password hygiene
</div>
<div class="flow-arrow" aria-hidden="true">→</div>
<div class="flow-step">
<strong>Simulate</strong><br>
Phishing campaigns and tabletop exercises
</div>
<div class="flow-arrow" aria-hidden="true">→</div>
<div class="flow-step">
<strong>Measure</strong><br>
Click rates, reports, culture surveys
</div>
<div class="flow-arrow" aria-hidden="true">→</div>
<div class="flow-step">
<strong>Improve</strong><br>
Targeted coaching and updated materials
</div>
</div>
</div>
<ul>
HTML,
];

foreach ($insertions as $file => $spec) {
    if (empty($spec['block'])) {
        echo "skip {$file} (no block)\n";
        continue;
    }
    $path = $lessonsdir . '/' . $file;
    $html = file_get_contents($path);
    if ($html === false) {
        fwrite(STDERR, "read_fail {$path}\n");
        continue;
    }
    if (strpos($html, 'ut-lesson-diagram') !== false) {
        echo "skip {$file} (already has diagram)\n";
        continue;
    }
    if (strpos($html, $spec['anchor']) === false) {
        fwrite(STDERR, "anchor_miss {$file}\n");
        continue;
    }
    $html = str_replace($spec['anchor'], $spec['block'], $html);
    file_put_contents($path, $html);
    echo "inserted {$file}\n";
}

echo "insert_missing_lesson_diagrams_complete=1\n";
