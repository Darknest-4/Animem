import type { MailMessage } from './message.js';

/**
 * The port every use case sends through.
 *
 * Use cases depend on this and never on a transport, so the same
 * `forgot password` code path is exercised identically by the SMTP driver in
 * production, the log driver in development and the memory driver in tests.
 */
export interface Mailer {
  send(message: MailMessage): Promise<void>;
}
