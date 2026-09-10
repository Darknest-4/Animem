import type { MailMessage } from '../domain/message.js';
import { paragraph, renderLayout, renderText, type LayoutInput } from './layout.js';

export interface PasswordChangedInput {
  readonly appName: string;
  readonly to: string;
  readonly username: string;
  readonly changedAt: Date;
  readonly supportUrl: string;
}

/**
 * The notification that closes the loop on a takeover.
 *
 * Sent after every successful password change, including one made through a
 * reset link. It is the only signal a user gets that someone else changed their
 * password, so it goes out even when the change was legitimate.
 */
export function passwordChangedMessage(input: PasswordChangedInput): MailMessage {
  const body = [
    `Hi ${input.username},`,
    `The password on your ${input.appName} account was changed on ${input.changedAt.toUTCString()}. Every other signed-in device has been signed out.`,
  ];

  const layout: LayoutInput = {
    appName: input.appName,
    heading: 'Your password was changed',
    bodyHtml: body.map(paragraph).join('\n'),
    action: { label: 'This was not me', url: input.supportUrl },
    footerNote: 'If you made this change, there is nothing else to do.',
  };

  return {
    to: input.to,
    subject: `Your ${input.appName} password was changed`,
    html: renderLayout(layout),
    text: renderText(layout, body),
    tag: 'password_changed',
  };
}
