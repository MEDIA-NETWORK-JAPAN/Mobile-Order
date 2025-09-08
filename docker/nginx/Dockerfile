FROM nginx:1.25-alpine

# Nginxの設定ファイルをコピー
COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf

# Laravelアプリケーションのpublicディレクトリをコピー（静的ファイルとPHPファイルの存在確認用）
COPY --chown=nginx:nginx ./public /var/www/html/public

# ヘルスチェック用エンドポイント
RUN echo "OK" > /usr/share/nginx/html/health

# ログ設定（ECS用）
RUN ln -sf /dev/stdout /var/log/nginx/access.log \
    && ln -sf /dev/stderr /var/log/nginx/error.log

# nginx ユーザーに必要な権限を設定
RUN chown -R nginx:nginx /var/cache/nginx \
    && chown -R nginx:nginx /var/log/nginx \
    && chown -R nginx:nginx /etc/nginx/conf.d \
    && touch /var/run/nginx.pid \
    && chown -R nginx:nginx /var/run/nginx.pid

# 実行ユーザー - ECS Fargateではrootで実行（ポート80のバインドに必要）
# USER nginx

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]