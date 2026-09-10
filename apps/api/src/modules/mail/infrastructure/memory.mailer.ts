import type { Mailer } from '../domain/mailer.js';
import type { MailMessage } from '../domain/message.js';

/**
 * Keeps messages in an array so tests can assert on them.
 *
 * Lets a test read the real token out of the real mail body and redeem it,
 * exercising the whole flow rather than reaching into the repository to fake a
 * token the production path would never have produced.
 */
export class MemoryMailer implements Mailer {
  private readonly messages: MailMessage[] = [];

  async send(message: MailMessage): Promise<void> {
    this.messages.push(message);

    return Promise.resolve();
  }

  get sent(): readonly MailMessage[] {
    return this.messages;
  }

  lastTo(address: string): MailMessage | undefined {
    return [...this.messages].reverse().find((message) => message.to === address);
  }

  /** The first URL in the text body — the link the recipient would click. */
  lastLinkTo(address: string): string | null {
    return /https?:\/\/\S+/u.exec(this.lastTo(address)?.text ?? '')?.[0] ?? null;
  }

  clear(): void {
    this.messages.length = 0;
  }
}
