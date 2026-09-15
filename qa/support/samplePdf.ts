/**
 * Generates a minimal-but-valid single-page PDF in memory (a colored cover
 * with title/author text) — used for upload tests instead of a committed
 * binary fixture, since .gitignore blanket-ignores *.pdf (matches the
 * generator in scripts/seed-sample-books.php).
 */
export function buildSamplePdf(title = 'QA Sample PDF', author = 'QA Test Author'): Buffer {
  const escape = (s: string): string => s.replace(/\\/g, '\\\\').replace(/\(/g, '\\(').replace(/\)/g, '\\)');

  const content =
    'q\n0.3 0.4 0.7 rg\n0 0 420 600 re f\nQ\n' +
    `BT /F1 22 Tf 1 0 0 1 30 520 Tm 1 1 1 rg (${escape(title)}) Tj ET\n` +
    `BT /F1 14 Tf 1 0 0 1 30 480 Tm 1 1 1 rg (by ${escape(author)}) Tj ET\n` +
    'BT /F1 10 Tf 1 0 0 1 30 60 Tm 1 1 1 rg (Dummy content - generated for E2E tests) Tj ET\n';

  const objects: Record<number, string> = {
    1: '<< /Type /Catalog /Pages 2 0 R >>',
    2: '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
    3:
      '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 420 600] ' +
      '/Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>',
    4: `<< /Length ${content.length} >>\nstream\n${content}endstream`,
    5: '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
  };

  let pdf = '%PDF-1.4\n';
  for (const [num, body] of Object.entries(objects)) {
    pdf += `${num} 0 obj\n${body}\nendobj\n`;
  }
  pdf += 'trailer\n<< /Root 1 0 R >>\n';

  return Buffer.from(pdf, 'latin1');
}
