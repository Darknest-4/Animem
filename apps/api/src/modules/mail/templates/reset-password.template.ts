import { Duration } from '@yume/core';

import type { MailMessage } from '../domain/message.js';
import { paragraph, renderLayout, renderText, type LayoutInput } from './layout.js';

export interface ResetPasswordInput {
  readonly appName: string;
  readonly to: string;
  readonly username: string;
  readonly resetUrl: string;
  readonly expiresInSeconds: number;
  /** Shown so a recipient who did not ask can tell where the request came from. */
  readonly requestedFromIp: string;
}

export function resetPasswordMessage(input: ResetPasswordInput): MailMessage {
  const validFor = `${String(Math.round(input.expiresInSeconds / Duration.minutes(1)))} minutes`;
  const body = [
    `Hi ${input.username},`,
    `Someone at ${input.requestedFromIp} asked to reset the password on your ${input.appName} account. The link below is valid for ${validFor} and can be used once.`,
  ];

  const layout: LayoutInput = {
    appName: input.appName,
    heading: 'Reset your password',
    bodyHtml: body.map(paragraph).join('\n'),
    action: { label: 'Choose a new password', url: input.resetUrl },
    footerNote:
      'If this was not you, no action is needed — your current password still works and this link expires on its own.',
  };

  return {
    to: input.to,
    subject: `Reset your ${input.appName} password`,
    html: renderLayout(layout),
    text: renderText(layout, body),
    tag: 'password_reset',
  };
}
