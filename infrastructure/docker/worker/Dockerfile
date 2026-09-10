# syntax=docker/dockerfile:1
#
# The worker shares the production image and only changes the entrypoint, so the
# code that runs a job is byte-identical to the code that served the request
# which enqueued it.

ARG YUME_IMAGE=yume-api:latest
FROM ${YUME_IMAGE} AS worker

USER www-data

# The queue is polled, so nothing listens on a port. Liveness is the heartbeat
# file the worker loop touches after each poll.
HEALTHCHECK --interval=30s --timeout=5s --start-period=15s --retries=3 \
    CMD php -r '$f="/var/www/html/apps/api/storage/tmp/worker.heartbeat"; exit(is_file($f) && (time()-(int)filemtime($f)) < 120 ? 0 : 1);'

ENTRYPOINT ["php", "/var/www/html/apps/api/bin/worker"]
