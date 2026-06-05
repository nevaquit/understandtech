import fs from "fs";
import path from "path";
import os from "os";
import { execSync } from "child_process";

function extractDocx(docxPath) {
  const tmp = fs.mkdtempSync(path.join(os.tmpdir(), "docx-"));
  const zipPath = path.join(tmp, "doc.zip");
  fs.copyFileSync(docxPath, zipPath);
  execSync(
    `powershell -NoProfile -Command "Expand-Archive -Path '${zipPath.replace(/'/g, "''")}' -DestinationPath '${tmp.replace(/'/g, "''")}' -Force"`,
    { stdio: "pipe" },
  );
  const xml = fs.readFileSync(path.join(tmp, "word", "document.xml"), "utf8");
  const chunks = xml.split(/<w:p[ >]/).slice(1);
  return chunks
    .map((chunk) => {
      const styleMatch = chunk.match(/<w:pStyle w:val="([^"]+)"/);
      const texts = [...chunk.matchAll(/<w:t[^>]*>([^<]*)<\/w:t>/g)]
        .map((m) => m[1])
        .join("");
      return { style: styleMatch ? styleMatch[1] : "", text: texts.trim() };
    })
    .filter((p) => p.text);
}

function normalize(text) {
  return text
    .replace(/\u2014/g, "—")
    .replace(/\u2013/g, "–")
    .replace(/\u2022/g, "-")
    .replace(/\u2192/g, "→")
    .replace(/\u201c|\u201d/g, '"')
    .replace(/\u2018|\u2019/g, "'");
}

function toMarkdown(paras, title) {
  const lines = [`# ${title}`, ""];
  let inPrompt = false;

  for (const p of paras) {
    const t = normalize(p.text);
    const s = p.style.toLowerCase();

    if (t.includes("CURSOR PROMPT")) {
      if (inPrompt) {
        lines.push("```", "");
        inPrompt = false;
      }
      lines.push("---", "", `### ${t}`, "", "```markdown");
      inPrompt = true;
      continue;
    }

    if (inPrompt && t.startsWith("Why this prompt:")) {
      lines.push("```", "", `**Why this prompt:** ${t.replace(/^Why this prompt:\s*/i, "")}`, "");
      inPrompt = false;
      continue;
    }

    if (/^heading1$|^title$/i.test(s)) {
      lines.push(`## ${t}`, "");
    } else if (/^heading2$/i.test(s)) {
      lines.push(`### ${t}`, "");
    } else if (/^heading3$/i.test(s)) {
      lines.push(`#### ${t}`, "");
    } else if (/^[A-Z0-9][A-Z0-9 \-—]{8,}$/.test(t) && t.length < 90) {
      lines.push(`## ${t}`, "");
    } else {
      lines.push(t, "");
    }
  }

  if (inPrompt) {
    lines.push("```", "");
  }

  return `${lines.join("\n").trim()}\n`;
}

const conversions = [
  [
    "docs/understandtech_app_White_Paper_v2.docx",
    "docs/white-paper.md",
    "understandtech.app Technical White Paper v2.0",
  ],
  [
    "docs/understandtech_app_Creation_Playbook.docx",
    "docs/playbook.md",
    "understandtech.app Creation Playbook",
  ],
];

for (const [src, dest, title] of conversions) {
  const paras = extractDocx(src);
  const md = toMarkdown(paras, title);
  fs.writeFileSync(dest, md, "utf8");
  console.log(`${dest}: ${paras.length} paragraphs, ${md.length} chars`);
}
