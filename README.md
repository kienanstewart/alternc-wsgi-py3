# Requirements

* libapache2-mod-wsgi-py3
* python3-pip
* python3-venv
* (possibly) alternc-nss

# Server configuration

    a2enmond proxy_uswgi
    a2enmod wsgi
    service apache2 restart

# Create virtual environments

    python3 -m venv --copies /path/to/virtualenv/name
    mv /path/to/virtualenv/name /var/www/alternc/X/Xname/venv/name

# Create/Deploy App

eg. Django

    mv /path/to/virtualenv/name
    source bin/activate
    pip install django
    cd /var/www/alternc/X/Xname/www
    # Note: projectname must not conflict with the name of the venv or any other python module
    django-admin startproject projectname

# Create apache configuration

    <VirtualHost *:80>
      ServerName test.alternc.local
      DocumentRoot /var/www/alternc/a/admin/www/test/ourcase/
      AssignUserId #2000 #2000
      SetEnv LOGIN "2000-admin"
      <IfModule mod_wsgi.c>
        WSGIDaemonProcess test.alternc.local python-home=/var/www/alternc/a/admin/venv/test/ python-path=/var/www/alternc/a/admin/www/test/ourcase/ socket-user=#2000
        WSGIProcessGroup test.alternc.local
        WSGIScriptAlias / /var/www/alternc/a/admin/www/test/ourcase/ourcase/wsgi.py process-group=test.alternc.local
      </IfModule>

      <Directory "/var/www/alternc/a/admin/www/test/ourcase/ourcase/">
        AllowOverride All
        <Files wsgi.py>
         <IfVersion < 2.4>
            Order allow,deny
            Allow from all
          </IfVersion>
          <IfVersion >= 2.4>
            Require all granted
          </IfVersion>
        </Files>
      </Directory>
    </VirtualHost>

Note: this requires '#2000' to resolve to a valid unix account. Therefore it doesn't work with the default AlternC structure. It may work with alternc-nss to provide passwd/group files and integrate with the rest of the unix system.

# Template for alternc

    <VirtualHost *:80>
      ServerName %%fqdn%%
      DocumentRoot %%document_root%%
      AssignUserId #%%UID%% #%%GID%%
      SetEnv LOGIN "%%UID%%-%%LOGIN%%"
      <IfModule mod_wsgi.c>
        WSGIDaemonProcess %%fqdn%% python-home=%%VENV%% python-path=%%document_root%%/%%APP_SUBDIR%% socket-user=#%%UID%%
        WSGIProcessGroup %%fqdn%%
        WSGIScriptAlias / %%document_root%%/%%APP_SUBDIR%%/wsgi.py process-group=%%fqdn%%
      </IfModule>

      <Directory "%%document_root%%/%%APP_SUBDIR%%">
        AllowOverride All
        <Files wsgi.py>
         <IfVersion < 2.4>
            Order allow,deny
            Allow from all
          </IfVersion>
          <IfVersion >= 2.4>
            Require all granted
          </IfVersion>
        </Files>
      </Directory>
    </VirtualHost>

# Todo

1. Confirm alternc-nss provides enough integration to allow wsgi to function
  using numeric IDs
2. Add templates for wsgi-http and wsgi-https
3. Add support for storing and replacing new `%%VENV%%`, `%%APP_SUBDIR%%` tokens
  in templates
4. Add UI to allow for adding WSGI applications (custom UI is required to handle
  the VENV and APP_SUBDIR tokens).
5. Debian package control, installation, and removal scripts

Further points of interest:

* Once application code, settings, etc. are changed an apache reload is required.
  Are there settings or a way to allow users to request a reload in an unobtrusive
  manner?
* Are there other steps that should be taken to prevent users applications from
  interfering with each other?
* Does it work to create venvs and project code elsewhere and upload via FTP?
* Is it work considering multi-process daemon setups or more complicated Vhost
  configurations?

# License

GPLv3+, see LICENSE.txt for the full text.
