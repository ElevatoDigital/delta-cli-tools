server {
    listen 80;
    server_name <?php echo $this->hostname;?> www.<?php echo $this->hostname;?>;

    location / {
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header Host "$host:8080";

        if ($is_args != "") {
            proxy_pass http://127.0.0.1:81;
        }

        if ($request_method = POST ) {
            proxy_pass http://127.0.0.1:81;
        }

        if ($http_cookie ~* "wordpress_logged_in") {
            proxy_pass http://127.0.0.1:81;
        }

        if ($http_cookie ~* "wp-postpass_") {
            proxy_pass http://127.0.0.1:81;
        }

        root <?php echo $this->docRoot;?>;
        index index.php index.html index.htm;
        try_files $uri $uri/ @fallback;
    }

    location ~ \.php$ {
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header Host "$host:8080";
        proxy_pass http://127.0.0.1:81;
    }

    location @fallback {
        proxy_set_header X-Real-IP  $remote_addr;
        proxy_set_header Host "$host:8080";
        proxy_pass http://127.0.0.1:81;
    }

    location ~ /\.ht {
        deny all;
    }
}
