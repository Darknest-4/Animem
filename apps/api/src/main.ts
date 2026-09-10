import closeWithGrace from 'close-with-grace';

import { loadConfig } from '@yume/config';
import { createLogger } from '@yume/logger';

import { createContainer } from './container/container.js';
import { createServer } from './server.js';

/**
 * The process entry point.
 *
 * Everything it does is sequencing: load configuration, build the container,
 * build the server, listen, and shut down cleanly. No logic lives here, because
 * anything that lives here is unreachable from a test.
 */
async function main(): Promise<void> {
  const config = loadConfig();
  const logger = createLogger({
    level: config.observability.logLevel,
    pretty: config.observability.logPretty,
    name: config.app.name,
    version: config.app.version,
  });

  const services = await createContainer({ config, logger });

  // Fail here rather than serving 500s: a container that cannot reach its
  // database should never report itself started, or an orchestrator will happily
  // roll a broken deployment all the way out.
  await services.database.ping();

  const app = await createServer(services);

  closeWithGrace({ delay: 10_000 }, async ({ signal, err }) => {
    if (err !== undefined) {
      logger.fatal('Shutting down after an unhandled error.', { error: err.message, stack: err.stack });
    } else {
      logger.info('Shutting down.', { signal });
    }

    // Order matters: stop accepting requests, let in-flight ones finish, then
    // release the pool. Closing the pool first would fail every request that was
    // already running.
    await app.close();
    await services.close();
  });

  await app.listen({ host: config.app.host, port: config.app.port });

  logger.info('API listening.', {
    url: `http://${config.app.host}:${String(config.app.port)}`,
    env: config.env,
    docs: config.isProduction ? null : `${config.app.url}/docs`,
  });
}

main().catch((error: unknown) => {
  // The logger may not exist yet — a configuration failure happens before it is
  // built — so this one message goes to stderr directly.
  process.stderr.write(
    `Failed to start: ${error instanceof Error ? (error.stack ?? error.message) : String(error)}\n`,
  );
  process.exitCode = 1;
});
