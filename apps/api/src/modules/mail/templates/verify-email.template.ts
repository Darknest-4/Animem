import { Duration } from '@yume/core';

import type { MailMessage } from '../domain/message.js';
import { paragraph, renderLayout, renderText, type LayoutInput } from './layout.js';

export interface VerifyEmailInput {
  readonly appName: string;
  readonly to: string;
  readonly username: string;
  readonly verifyUrl: string;
  readonly expiresInSeconds: number;
}

export function verifyEmailMessage(input: VerifyEmailInput): MailMessage {
  const validFor = `${String(Math.round(input.expiresInSeconds / Duration.hours(1)))} hours`;
  const body = [
    `Hi ${input.username}, welcome to ${input.appName}.`,
    `Confirm this address to finish setting up your account. The link is valid for ${validFor}.`,
  ];

  const layout: LayoutInput = {
    appName: input.appName,
    heading: 'Confirm your email address',
    bodyHtml: body.map(paragraph).join('\n'),
    action: { label: 'Confirm my address', url: input.verifyUrl },
    footerNote:
      'If you did not create this account, ignore this message — the address will not be used again.',
  };

  return {
    to: input.to,
    subject: `Confirm your ${input.appName} account`,
    html: renderLayout(layout),
    text: renderText(layout, body),
    tag: 'email_verification',
  };
}
