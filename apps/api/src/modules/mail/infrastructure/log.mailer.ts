import type { Logger } from '@yume/logger';

import type { Mailer } from '../domain/mailer.js';
import type { MailMessage } from '../domain/message.js';

/** Matches the first http(s) URL in the text body. */
const LINK_PATTERN = /https?:\/\/\S+/u;

/**
 * Writes mail to the log instead of sending it.
 *
 * The development default. It prints the action link on its own field so a
 * developer can verify an address or reset a password without an SMTP server,
 * a mail catcher, or commenting the send out — which is how verification ends
 * up permanently disabled locally and then, once, in production.
 */
export class LogMailer implements Mailer {
  constructor(private readonly logger: Logger) {}

  async send(message: MailMessage): Promise<void> {
    this.logger.info('Mail (not sent — log driver).', {
      to: message.to,
      subject: message.subject,
      tag: message.tag,
      link: LINK_PATTERN.exec(message.text)?.[0] ?? null,
    });

    return Promise.resolve();
  }
}
