import type { AuthDependencies } from './auth.dependencies.js';
import { ChangePasswordUseCase } from './change-password.usecase.js';
import { ForgotPasswordUseCase } from './forgot-password.usecase.js';
import { ListSessionsUseCase } from './list-sessions.usecase.js';
import { LoginUseCase } from './login.usecase.js';
import { LogoutUseCase } from './logout.usecase.js';
import { RegisterUseCase } from './register.usecase.js';
import { ResendVerificationUseCase } from './resend-verification.usecase.js';
import { ResetPasswordUseCase } from './reset-password.usecase.js';
import { RevokeSessionUseCase } from './revoke-session.usecase.js';
import { SessionAuthenticator } from './session-authenticator.service.js';
import { VerifyEmailUseCase } from './verify-email.usecase.js';

export interface AuthUseCases {
  readonly register: RegisterUseCase;
  readonly login: LoginUseCase;
  readonly logout: LogoutUseCase;
  readonly listSessions: ListSessionsUseCase;
  readonly revokeSession: RevokeSessionUseCase;
  readonly verifyEmail: VerifyEmailUseCase;
  readonly resendVerification: ResendVerificationUseCase;
  readonly forgotPassword: ForgotPasswordUseCase;
  readonly resetPassword: ResetPasswordUseCase;
  readonly changePassword: ChangePasswordUseCase;
  readonly authenticator: SessionAuthenticator;
}

/**
 * Instantiates the module's use cases once.
 *
 * They are stateless and share one dependency bundle, so building them per
 * request would allocate eleven objects to do nothing. Assembling them here also
 * keeps the composition root from having to know each constructor.
 */
export function createAuthUseCases(deps: AuthDependencies): AuthUseCases {
  return Object.freeze({
    register: new RegisterUseCase(deps),
    login: new LoginUseCase(deps),
    logout: new LogoutUseCase(deps),
    listSessions: new ListSessionsUseCase(deps),
    revokeSession: new RevokeSessionUseCase(deps),
    verifyEmail: new VerifyEmailUseCase(deps),
    resendVerification: new ResendVerificationUseCase(deps),
    forgotPassword: new ForgotPasswordUseCase(deps),
    resetPassword: new ResetPasswordUseCase(deps),
    changePassword: new ChangePasswordUseCase(deps),
    authenticator: new SessionAuthenticator(deps),
  });
}
