# ================================================
# Mobile Order System - Nginx Reverse Proxy
# ベストプラクティス: セキュリティ・パフォーマンス最適化
# ================================================

FROM nginx:1.25-alpine

# Install security updates
RUN apk upgrade --no-cache \
    && rm -rf /var/cache/apk/*

# Create nginx user and group with specific UID/GID for consistency
RUN addgroup -g 1000 -S nginxgroup \
    && adduser -u 1000 -S nginxuser -G nginxgroup

# Copy configuration files
COPY aws/application/containers/config/nginx.conf /etc/nginx/nginx.conf
COPY aws/application/containers/config/default.conf /etc/nginx/conf.d/default.conf

# Copy static files (Laravel public directory)
COPY --chown=nginxuser:nginxgroup ./public /var/www/html/public

# Security headers and health check
COPY aws/application/containers/config/security-headers.conf /etc/nginx/conf.d/security-headers.conf

# Create health check endpoint
RUN echo "OK" > /usr/share/nginx/html/health \
    && chown nginxuser:nginxgroup /usr/share/nginx/html/health

# Set up logging for ECS (stdout/stderr)
RUN ln -sf /dev/stdout /var/log/nginx/access.log \
    && ln -sf /dev/stderr /var/log/nginx/error.log

# Fix permissions for nginx to run as non-root
RUN chown -R nginxuser:nginxgroup /var/cache/nginx \
    && chown -R nginxuser:nginxgroup /var/log/nginx \
    && chown -R nginxuser:nginxgroup /etc/nginx/conf.d \
    && touch /var/run/nginx.pid \
    && chown -R nginxuser:nginxgroup /var/run/nginx.pid \
    && chmod 755 /var/cache/nginx \
    && chmod 755 /var/log/nginx

# Security: Run as non-root user
USER nginxuser

EXPOSE 8080

# Health check
HEALTHCHECK --interval=30s --timeout=5s --start-period=30s --retries=3 \
    CMD curl -f http://localhost:8080/health || exit 1

CMD ["nginx", "-g", "daemon off;"]