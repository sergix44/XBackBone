<x-menu-item title="Gallery" icon="o-photo" link="{{ route('dashboard') }}"/>
<x-menu-item title="Integrations" icon="o-cog" link="{{ route('integrations') }}"/>
<x-menu-item title="Settings" icon="o-adjustments-vertical" link="{{ route('admin.settings') }}" :enabled="auth()->user()?->can('administrate')"/>
