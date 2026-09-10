import { z } from 'zod';

/** RFC 9457. The only error shape the API produces. */
export const problemSchema = z
  .object({
    type: z.string(),
    title: z.string(),
    status: z.number().int(),
    code: z.string(),
    detail: z.string(),
    requestId: z.string().optional(),
  })
  .passthrough();

export type Problem = z.infer<typeof problemSchema>;

/** Field-level failures, carried as an extension on a 422. */
export const validationProblemSchema = problemSchema.extend({
  issues: z.record(z.array(z.string())).optional(),
});

export type ValidationProblem = z.infer<typeof validationProblemSchema>;
