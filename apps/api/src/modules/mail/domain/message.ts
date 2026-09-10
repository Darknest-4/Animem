/**
 * One outbound message, driver-agnostic.
 *
 * Both bodies are always present. A text alternative is not politeness — a
 * verification link that only exists inside HTML is unreachable to anyone whose
 * client blocks it, and mail that carries no text part scores as spam.
 */
export interface MailMessage {
  readonly to: string;
  readonly subject: string;
  readonly html: string;
  readonly text: string;
  /** Correlates the delivery attempt with the request that caused it. */
  readonly tag: string;
}
