'use strict';
const fs = require('fs');
const path = require('path');

async function main() {
  const pdfPath = process.argv[2];
  const outPath = process.argv[3];
  if (!pdfPath || !outPath) {
    console.error('Usage: node extract-aplus-flashcards-pdf.cjs <pdf> <out.txt>');
    process.exit(1);
  }
  const { PDFParse } = require('pdf-parse');
  const buf = fs.readFileSync(pdfPath);
  const parser = new PDFParse({ data: buf });
  const data = await parser.getText();
  fs.mkdirSync(path.dirname(outPath), { recursive: true });
  fs.writeFileSync(outPath, data.text, 'utf8');
  console.log(JSON.stringify({ pages: data.total, chars: data.text.length, out: outPath }));
  console.log('--- preview ---');
  console.log(data.text.slice(0, 4000));
}

main().catch((err) => {
  console.error(err);
  process.exit(1);
});
