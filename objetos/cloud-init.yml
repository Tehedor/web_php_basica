#cloud-config
# minioadmin/minioadmin
# http://<IP_DEL_SERVIDOR>:9000/images/image.jpg
package_update: true
packages:
  - wget
  - git
  - unzip

users:
  - name: minio-user
    shell: /bin/bash
    sudo: ['ALL=(ALL) NOPASSWD:ALL']
    lock_passwd: false

write_files:
  - path: /etc/systemd/system/minio.service
    owner: root:root
    permissions: '0644'
    content: |
      [Unit]
      Description=MinIO
      After=network.target

      [Service]
      User=minio-user
      Group=minio-user
      Environment="MINIO_ROOT_USER=minioadmin"
      Environment="MINIO_ROOT_PASSWORD=minioadmin"
      ExecStart=/usr/local/bin/minio server /home/minio-user/minio_data --address ":9000" --console-address ":9001"
      Restart=always
      LimitNOFILE=65536

      [Install]
      WantedBy=multi-user.target

runcmd:
  # Crear directorios para MinIO
  - [ bash, -lc, 'mkdir -p /home/minio-user/minio_data' ]
  - [ bash, -lc, 'chown -R minio-user:minio-user /home/minio-user/minio_data' ]

  # Descargar MinIO server y cliente (mc)
  - [ bash, -lc, 'mkdir -p /usr/local/bin && wget -q https://dl.min.io/server/minio/release/linux-amd64/minio -O /usr/local/bin/minio' ]
  - [ bash, -lc, 'wget -q https://dl.min.io/client/mc/release/linux-amd64/mc -O /usr/local/bin/mc' ]
  - [ bash, -lc, 'chmod +x /usr/local/bin/minio /usr/local/bin/mc' ]

  # Clonar repositorio con la imagen
  - [ bash, -lc, 'mkdir -p /tmp/web_clone && git clone --depth=1 https://github.com/Tehedor/web_php_basica.git /tmp/web_clone' ]

  # Configurar y arrancar MinIO
  - [ bash, -lc, 'systemctl daemon-reload' ]
  - [ bash, -lc, 'systemctl enable minio' ]
  - [ bash, -lc, 'systemctl start minio' ]

  # Esperar a que MinIO esté activo
  - [ bash, -lc, 'sleep 10' ]

  # Crear bucket "images" y subir imagen con MinIO Client
  - [ bash, -lc, '/usr/local/bin/mc alias set local http://localhost:9000 minioadmin minioadmin --api s3v4' ]
  - [ bash, -lc, '/usr/local/bin/mc mb local/images || true' ]
  - [ bash, -lc, '/usr/local/bin/mc cp /tmp/web_clone/objetos/image.jpg local/images/image.jpg' ]

  # Hacer público el bucket para acceso directo
  - [ bash, -lc, '/usr/local/bin/mc anonymous set download local/images' ]
