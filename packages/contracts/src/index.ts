/**
 * The API contract, shared by the server and the web client.
 *
 * The server validates requests and serialises responses with these schemas;
 * the web app parses responses with the same ones. A field renamed on one side
 * therefore fails to compile on the other, which is the whole reason this lives
 * in its own package rather than being written twice.
 */
export * from './common/index.js';
export * from './auth/index.js';
export * from './catalogue/index.js';
export * from './admin/index.js';
