@foreach($groups as $group)
<li class="tree-item">
    <a href="{{ route('tenants.index', ['group' => $group->id]) }}" 
       class="tree-item-content {{ request('group') == $group->id ? 'active' : '' }}"
       style="padding-left: {{ 12 + ($level * 16) }}px;">
        @if($group->children->count() > 0)
            <span class="tree-toggle">{{ request('group') == $group->id ? '-' : '+' }}</span>
        @else
            <span class="tree-toggle"></span>
        @endif
        <span class="tree-icon">
            @if($group->type === 'folder')
                📁
            @elseif($group->type === 'ip_range')
                🌐
            @else
                🖥️
            @endif
        </span>
        <span class="tree-name">{{ $group->name }}</span>
        @if($group->assets()->count() > 0)
            <span class="tree-badge">{{ $group->assets()->count() }}</span>
        @endif
    </a>
    @if($group->children->count() > 0)
        <ul class="tree-children" style="{{ request('group') == $group->id ? '' : 'display: none;' }}">
            @include('tenants.partials.tree-item', ['groups' => $group->children, 'level' => $level + 1])
        </ul>
    @endif
</li>
@endforeach
