/**
 * Strip copyright footers, lab activities, and vendor branding from Ebook PDF text/HTML.
 */

/** @param {string} raw */
export function sanitizeEbookText(raw) {
  let text = raw.replace(/\r\n/g, '\n');

  text = text.replace(/-- \d+ of \d+ --/g, '');
  text = text.replace(/Copyright ©[^\n]*/gi, '');
  text = text.replace(/LICENSED FOR USE ONLY BY:[^\n]*/gi, '');
  text = text.replace(/All rights reserved\.?[^\n]*/gi, '');

  text = text.replace(/\s*\(Images?\s+(?:from user [^)]+)?©[^)]+\)/gi, '');
  text = text.replace(/\s*\(Image\s+by\s+[^)]+©[^)]+\)/gi, '');
  text = text.replace(/^Images?\s+©\s*123rf\.com\.?\s*$/gim, '');
  text = text.replace(/^Image\s+credit:\s*[^©\n]+©\s*123rf\.com\.?\s*$/gim, '');
  text = text.replace(/^Image\s+©\s*[^\n]*123RF\.com\s*$/gim, '');
  text = text.replace(/Screenshot ©[^.\n]*MITRE Corporation[^.\n]*\.?/gi, '');
  text = text.replace(
    /This work is reproduced and distributed with the permission of The MITRE Corporation\.?/gi,
    ''
  );

  text = text.replace(/CompTIA Security\+ Exam SY0-701/gi, 'Security+ SY0-701');
  text = text.replace(/CompTIA Security\+/gi, 'Security+');
  text = text.replace(/CompTIA\.org/gi, '');
  text = text.replace(/CertMaster\s*(Learn|Practice|Labs)?/gi, '');
  text = text.replace(/Exam\s*Cram/gi, '');
  text = text.replace(/EXAM\s*CRAM/gi, '');
  text = text.replace(/JUST THE FORMULAS![^\n]*/gi, '');
  text = text.replace(/^CISSP real-world example![^\n]*$/gim, '');
  text = text.replace(/Remember, the Official Study Guide suggests\s*/gi, '');
  text = text.replace(/^Available on\s*$/gim, '');
  text = text.replace(/D O M A I N \d+\s*:\s*/g, '');
  text = text.replace(/Pearson IT Certification/gi, '');
  text = text.replace(/Pearson VUE/gi, '');

  text = text.replace(
    /Lab\s+Activity[\s\S]*?(?=Topic\s+\d+[A-D]|Review\s+Activity|Copyright|$)/gi,
    ''
  );
  text = text.replace(
    /Review\s+Activity[\s\S]*?(?=Topic\s+\d+[A-D]|Lab\s+Activity|Copyright|$)/gi,
    ''
  );
  text = text.replace(
    /Hands-?On\s+Lab[\s\S]*?(?=Topic\s+\d+[A-D]|Lab\s+Activity|Review\s+Activity|$)/gi,
    ''
  );

  text = text.replace(/^Links in video description\s*$/gim, '');
  text = text.replace(/^SECURITY\+[^\n]{0,30}$/gim, '');

  const lines = text.split('\n').map((line) => {
    let trimmed = line.trim();
    trimmed = trimmed.replace(/\s+Images?\s+©\s*123rf\.com\.?\s*$/i, '');
    trimmed = trimmed.replace(/\s+\d{1,3}$/, (m) => (trimmed.length < 70 ? '' : m));
    return trimmed;
  });
  text = lines.join('\n');

  text = text.replace(/^\d{1,2}\s*$/gm, '');
  text = text.replace(/^Lesson \d+\s*$/gm, '');
  text = text.replace(/^Objectives\s*$/gm, '');
  text = text.replace(/\n{3,}/g, '\n\n');
  return text.trim();
}

/** @param {string} html */
export function sanitizeEbookHtml(html) {
  return html
    .split('\n')
    .map((line) => line.replace(/\s*\(Images?\s+(?:from user [^)]+)?©[^)]+\)/gi, ''))
    .filter((line) => {
      const plain = line.replace(/<[^>]+>/g, '').replace(/\s+/g, ' ').trim();
      if (!plain) {
        return true;
      }
      if (/^Images?\s+©\s*123rf/i.test(plain)) {
        return false;
      }
      if (/^Image\s+(credit:|©)/i.test(plain)) {
        return false;
      }
      if (/123RF\.com/i.test(plain) && plain.length < 100) {
        return false;
      }
      if (/MITRE Corporation/i.test(plain)) {
        return false;
      }
      if (/EXAM\s*CRAM|JUST THE FORMULAS|CISSP real-world example/i.test(plain)) {
        return false;
      }
      if (/^Official Study Guide/i.test(plain)) {
        return false;
      }
      if (/^Available on\s*$/i.test(plain)) {
        return false;
      }
      if (/^Copyright ©/i.test(plain)) {
        return false;
      }
      if (/LICENSED FOR USE ONLY BY/i.test(plain)) {
        return false;
      }
      if (/^Lab\s+Activity/i.test(plain)) {
        return false;
      }
      if (/^Review\s+Activity/i.test(plain)) {
        return false;
      }
      if (/^Hands-?On\s+Lab/i.test(plain)) {
        return false;
      }
      return true;
    })
    .join('\n');
}
