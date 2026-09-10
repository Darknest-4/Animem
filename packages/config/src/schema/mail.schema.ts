import { z } from 'zod';

export const mailSchema = z.object({
  /** `log` writes the message to the structured log instead of sending it. */
  MAIL_DRIVER: z.enum(['log', 'smtp', 'null']).default('log'),
  MAIL_HOST: z.string().default('mailpit'),
  MAIL_PORT: z.coerce.number().int().min(1).max(65535).default(1025),
  MAIL_FROM: z.string().email().default('noreply@yume.local'),
  MAIL_USERNAME: z.string().optional(),
  MAIL_PASSWORD: z.string().optional(),
  MAIL_TIMEOUT_SECONDS: z.coerce.number().int().min(1).max(60).default(10),
});

export type MailEnv = z.infer<typeof mailSchema>;
