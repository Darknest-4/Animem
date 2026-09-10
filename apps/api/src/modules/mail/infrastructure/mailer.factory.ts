import type { Config } from '@yume/config';
import type { Logger } from '@yume/logger';

import type { Mailer } from '../domain/mailer.js';
import { LogMailer } from './log.mailer.js';
import { NullMailer } from './null.mailer.js';
import { SmtpMailer } from './smtp.mailer.js';

/**
 * Picks the transport from configuration.
 *
 * The one place a driver name maps to a class, so nothing else in the codebase
 * ever branches on `config.mail.driver`.
 */
export function createMailer(config: Config, logger: Logger): Mailer {
  switch (config.mail.driver) {
    case 'smtp':
      return new SmtpMailer(
        {
          host: config.mail.host,
          port: config.mail.port,
          from: config.mail.from,
          username: config.mail.username,
          password: config.mail.password,
          timeoutSeconds: config.mail.timeoutSeconds,
        },
        logger,
      );
    case 'log':
      return new LogMailer(logger);
    case 'null':
      return new NullMailer();
  }
}
