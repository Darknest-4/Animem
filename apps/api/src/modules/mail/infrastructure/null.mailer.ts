import type { Mailer } from '../domain/mailer.js';
import type { MailMessage } from '../domain/message.js';

/**
 * Discards everything.
 *
 * For test runs and for a staging environment restored from a production dump,
 * where the addresses are real people who never signed up for a staging test.
 */
export class NullMailer implements Mailer {
  async send(_message: MailMessage): Promise<void> {
    return Promise.resolve();
  }
}
