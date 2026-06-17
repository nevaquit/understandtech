/**
 * Clean CyberKraft Orange Study Guide PDF text extraction noise.
 *
 * @param {string} raw
 * @returns {string}
 */
export function sanitizeNetworkOrangeText(raw) {
  return raw
    .replace(/\r\n/g, '\n')
    .replace(/\u00ad/g, '')
    .replace(/[ \t]+\n/g, '\n')
    .split('\n')
    .map((line) => line.replace(/\s+$/g, ''))
    .filter((line, idx, arr) => !(line === '' && arr[idx - 1] === ''))
    .join('\n')
    .replace(/\n{3,}/g, '\n\n')
    .trim();
}

/**
 * @param {string} line
 * @returns {boolean}
 */
export function isOrangeNoiseLine(line) {
  const t = line.trim();
  if (!t) {
    return true;
  }
  return (
    /^THE ORANGE STUDY GUIDE/i.test(t) ||
    /^N10-009 Exam Preparation Page/i.test(t) ||
    /^-- \d+ of \d+ --$/.test(t) ||
    /^DOMAIN \d+\.0/i.test(t) ||
    /Exam Weight:/i.test(t) ||
    /^For personal exam preparation/i.test(t) ||
    /^Not affiliated with CompTIA/i.test(t) ||
    /^\u2014 \d+ \u2014$/.test(t) ||
    /^— \d+ —$/.test(t)
  );
}
