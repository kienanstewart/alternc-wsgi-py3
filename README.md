# Requirements

* alternc with subdomain hooks
  * This is a series of patches to allow plugins to interact with certain parts
  of the AlternC core which aren't yet included upstream.
  @see https://github.com/kienanstewart/AlternC/tree/subdomain_hooks
* libapache2-mod-wsgi-py3
* alternc-nss
  * alternc-nss provides unix user integration without which wsgi cannot look up
  uid to users properly.

Recommends:

* python3-pip
* python3-venv

# Server configuration

    a2enmond proxy_uswgi
    a2enmod wsgi
    service apache2 restart

# Create virtual environments

    python3 -m venv --copies /path/to/virtualenv/name
    mv /path/to/virtualenv/name /var/www/alternc/X/Xname/venv/name

## Creating environments remotely

Normally users in AlternC do not have shell access to run commands to manage
virtual environments. There are are couple of ways it may work (though it may
not always do so!).

Ideally users in a remote environment build their applications with the same
version of python as used on the AlternC host. In such a case, the venv can be
copied normally.

Option 1: Hack venv to switch python version

1. Create the venv normally
2. Activate and install all modules necessary
3. Modify pyvenv.cfg and change the python version listed to match the AlternC
host's python version
4. Create a symlink in the lib/ folder so both version work:

    ln -s python-<local_version> lib/python-<alternc_host_version>


Option 2: virtualenv

It is possible with virtualenv to use a non-system python and make it
relocatable; however, this functionality may not work 100% and may be
deprecated.

1. Install non-system python in /opt/ which matches the version on the AlternC
host.
2. Create virtualenv: `virtualenv --python=/opt/python-<alternc_host_version --relocatable <name>`

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

1. Validation/sanitization of the app_subdir and venv (especially relative paths).

Further points of interest:

* Once application code, settings, etc. are changed an apache reload is required.
  Are there settings or a way to allow users to request a reload in an unobtrusive
  manner?
* Are there other steps that should be taken to prevent users applications from
  interfering with each other?
* Is it work considering multi-process daemon setups or more complicated Vhost
  configurations?
* When a subdomain with WSGI is misconfigured, or the app is misconfigured, the
  processes can spam the error log a lot. Is it possible to limit the number of
  retries?

# License

GPLv3+, see LICENSE.txt for the full text.
