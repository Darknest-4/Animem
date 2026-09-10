import { createTransport, type Transporter } from 'nodemailer';

import type { Logger } from '@yume/logger';

import type { Mailer } from '../domain/mailer.js';
import type { MailMessage } from '../domain/message.js';

export interface SmtpOptions {
  readonly host: string;
  readonly port: number;
  readonly from: string;
  readonly username: string | undefined;
  readonly password: string | undefined;
  readonly timeoutSeconds: number;
}

/**
 * The production transport.
 *
 * Failures are logged and swallowed rather than propagated. A registration must
 * not fail because the mail relay is down: the account exists, and a
 * "resend verification" link recovers it. The alternative — a 500 after the row
 * is committed — leaves the user believing registration failed while the
 * username is taken.
 */
export class SmtpMailer implements Mailer {
  private readonly transporter: Transporter;

  constructor(
    options: SmtpOptions,
    private readonly logger: Logger,
  ) {
    this.from = options.from;
    this.transporter = createTransport({
      host: options.host,
      port: options.port,
      // Implicit TLS on 465; everything else negotiates STARTTLS, which
      // `requireTLS` makes mandatory rather than best-effort.
      secure: options.port === 465,
      requireTLS: options.port !== 465,
      ...(options.username === undefined || options.password === undefined
        ? {}
        : { auth: { user: options.username, pass: options.password } }),
      connectionTimeout: options.timeoutSeconds * 1000,
      greetingTimeout: options.timeoutSeconds * 1000,
      socketTimeout: options.timeoutSeconds * 1000,
    });
  }

  private readonly from: string;

  async send(message: MailMessage): Promise<void> {
    try {
      await this.transporter.sendMail({
        from: this.from,
        to: message.to,
        subject: message.subject,
        text: message.text,
        html: message.html,
      });

      this.logger.info('Mail sent.', { to: message.to, tag: message.tag });
    } catch (error) {
      this.logger.error('Mail delivery failed.', {
        to: message.to,
        tag: message.tag,
        error: error instanceof Error ? error.message : String(error),
      });
    }
  }

  /** Used by the readiness probe so a broken relay is visible before a user finds it. */
  async verify(): Promise<boolean> {
    try {
      await this.transporter.verify();

      return true;
    } catch {
      return false;
    }
  }

  close(): void {
    this.transporter.close();
  }
}
