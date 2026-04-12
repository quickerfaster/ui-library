<div>
<x-qf::navigation-layout configKey="admin.user" context="Users & Permissions" moduleName="admin" :overrides=[]>
       <livewire:qf.access-control-manager
        :selectedModule="$selectedModule?? null"
        :isUrlAccess="$isUrlAccess?? false"
        />
</x-qf::navigation-layout>
</div>

