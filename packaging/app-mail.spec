
Name: app-mail
Epoch: 1
Version: 2.1.6
Release: 1%{dist}
Summary: Mail Settings
License: GPLv3
Group: ClearOS/Apps
Source: %{name}-%{version}.tar.gz
Buildarch: noarch
Requires: %{name}-core = 1:%{version}-%{release}
Requires: app-base
Requires: app-network

%description
The mail app provides options for mail notification and simple outbound mail support.

%package core
Summary: Mail Settings - Core
License: LGPLv3
Group: ClearOS/Libraries
Requires: app-base-core
Requires: app-mail-notification-core
Requires: app-network-core
Requires: app-openldap-core => 1:1.1.4
Requires: app-smtp-core
Requires: postfix

%description core
The mail app provides options for mail notification and simple outbound mail support.

This package provides the core API and libraries.

%prep
%setup -q
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/mail
cp -r * %{buildroot}/usr/clearos/apps/mail/

install -D -m 0755 packaging/openldap-online-event %{buildroot}/var/clearos/events/openldap_online/mail

%post
logger -p local6.notice -t installer 'app-mail - installing'

%post core
logger -p local6.notice -t installer 'app-mail-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/mail/deploy/install ] && /usr/clearos/apps/mail/deploy/install
fi

[ -x /usr/clearos/apps/mail/deploy/upgrade ] && /usr/clearos/apps/mail/deploy/upgrade

exit 0

%preun
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-mail - uninstalling'
fi

%preun core
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-mail-core - uninstalling'
    [ -x /usr/clearos/apps/mail/deploy/uninstall ] && /usr/clearos/apps/mail/deploy/uninstall
fi

exit 0

%files
%defattr(-,root,root)
/usr/clearos/apps/mail/controllers
/usr/clearos/apps/mail/htdocs
/usr/clearos/apps/mail/views

%files core
%defattr(-,root,root)
%exclude /usr/clearos/apps/mail/packaging
%dir /usr/clearos/apps/mail
/usr/clearos/apps/mail/deploy
/usr/clearos/apps/mail/language
/usr/clearos/apps/mail/libraries
/var/clearos/events/openldap_online/mail
