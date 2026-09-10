/**
 * The one place mail markup is written.
 *
 * Every template renders through here, so the brand, the footer and the
 * "you can ignore this" line are defined once. Deliberately plain inline CSS:
 * mail clients discard stylesheets, and a table-based layout that renders in
 * Outlook is worth more than one that looks clever in a browser.
 */
export interface LayoutInput {
  readonly appName: string;
  readonly heading: string;
  /** Already-escaped HTML paragraphs. */
  readonly bodyHtml: string;
  readonly action?: { readonly label: string; readonly url: string };
  readonly footerNote: string;
}

const ESCAPES: Readonly<Record<string, string>> = Object.freeze({
  '&': '&amp;',
  '<': '&lt;',
  '>': '&gt;',
  '"': '&quot;',
  "'": '&#39;',
});

/** Escapes interpolated values. Every template runs user data through this. */
export function escapeHtml(value: string): string {
  return value.replace(/[&<>"']/gu, (character) => ESCAPES[character] ?? character);
}

export function paragraph(text: string): string {
  return `<p style="margin:0 0 16px;font-size:15px;line-height:1.6;color:#3f3f46;">${escapeHtml(text)}</p>`;
}

export function renderLayout(input: LayoutInput): string {
  const button =
    input.action === undefined
      ? ''
      : `<p style="margin:0 0 24px;">
           <a href="${escapeHtml(input.action.url)}"
              style="display:inline-block;padding:12px 24px;border-radius:8px;background:#6d28d9;color:#ffffff;font-size:15px;font-weight:600;text-decoration:none;">
             ${escapeHtml(input.action.label)}
           </a>
         </p>
         <p style="margin:0 0 16px;font-size:13px;line-height:1.6;color:#71717a;">
           If the button does not work, paste this address into your browser:<br />
           <span style="word-break:break-all;">${escapeHtml(input.action.url)}</span>
         </p>`;

  return `<!doctype html>
<html lang="en"><head><meta charset="utf-8" /><meta name="viewport" content="width=device-width,initial-scale=1" />
<title>${escapeHtml(input.heading)}</title></head>
<body style="margin:0;padding:24px;background:#f4f4f5;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;margin:0 auto;background:#ffffff;border-radius:12px;">
    <tr><td style="padding:32px;">
      <p style="margin:0 0 24px;font-size:13px;letter-spacing:0.08em;text-transform:uppercase;color:#6d28d9;font-weight:700;">${escapeHtml(input.appName)}</p>
      <h1 style="margin:0 0 16px;font-size:22px;line-height:1.3;color:#18181b;">${escapeHtml(input.heading)}</h1>
      ${input.bodyHtml}
      ${button}
      <hr style="border:none;border-top:1px solid #e4e4e7;margin:24px 0;" />
      <p style="margin:0;font-size:13px;line-height:1.6;color:#71717a;">${escapeHtml(input.footerNote)}</p>
    </td></tr>
  </table>
</body></html>`;
}

/** The plain-text alternative, built from the same inputs. */
export function renderText(input: LayoutInput, bodyText: readonly string[]): string {
  const lines = [input.heading, '', ...bodyText];

  if (input.action !== undefined) {
    lines.push('', `${input.action.label}: ${input.action.url}`);
  }

  lines.push('', input.footerNote, '', `— ${input.appName}`);

  return lines.join('\n');
}
