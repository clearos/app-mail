
Name: app-mail
Epoch: 1
Version: 1.1.4
Release: 1%{dist}
Summary: Mail Library - Core
License: LGPLv3
Group: ClearOS/Libraries
Source: app-mail-%{version}.tar.gz
Buildarch: noarch

%description
The Mail app is a very simple library that handles core mail parameters for other mail apps.

%package core
Summary: Mail Library - Core
Requires: app-base-core
Requires: app-network-core
Requires: app-openldap-core => 1:1.1.4

%description core
The Mail app is a very simple library that handles core mail parameters for other mail apps.

This package provides the core API and libraries.

%prep
%setup -q
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/mail
cp -r * %{buildroot}/usr/clearos/apps/mail/


%post core
logger -p local6.notice -t installer 'app-mail-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/mail/deploy/install ] && /usr/clearos/apps/mail/deploy/install
fi

[ -x /usr/clearos/apps/mail/deploy/upgrade ] && /usr/clearos/apps/mail/deploy/upgrade

exit 0

%preun core
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-mail-core - uninstalling'
    [ -x /usr/clearos/apps/mail/deploy/uninstall ] && /usr/clearos/apps/mail/deploy/uninstall
fi

exit 0

%files core
%defattr(-,root,root)
%exclude /usr/clearos/apps/mail/packaging
%exclude /usr/clearos/apps/mail/tests
%dir /usr/clearos/apps/mail
/usr/clearos/apps/mail/deploy
/usr/clearos/apps/mail/language
/usr/clearos/apps/mail/libraries
