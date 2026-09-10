import type { Services } from './container/services.js';
import type { RouteDefinition } from './http/route.js';
import { authRoutes } from './modules/auth/presentation/auth.routes.js';
import { healthRoutes } from './modules/health/presentation/health.routes.js';

/**
 * Every route in the application, in one list.
 *
 * A module exposes a function returning its declarations rather than registering
 * itself against the app, so the full surface is enumerable without booting a
 * server — which is what lets a test assert invariants across all of it, such as
 * "no write route is CSRF-exempt unless it says why".
 */
export function allRoutes(services: Services): readonly RouteDefinition[] {
  return [...healthRoutes(services), ...authRoutes(services)];
}
