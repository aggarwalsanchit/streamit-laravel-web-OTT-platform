@props([
    'url' => '',
    'entity_name' => 'item',
    'entity_name_plural' => 'items'
])
<form action="{{$url ?? ''}}" id="quick-action-form" class="form-disabled d-flex gap-3 align-items-stretch flex-wrap">
  @csrf
  {{$slot}}
  @php
    $entityName = $entity_name ?? 'item';
    $entityNamePlural = $entity_name_plural ?? $entityName;
    $entityNameCapitalized = ucfirst($entityName);
    $entityNamePluralCapitalized = ucfirst($entityNamePlural);
  @endphp
  <input type="hidden" name="message_change-featured" value="{{ __('messages.message_change-featured_dynamic', ['entity' => ':entity']) }}">
  <input type="hidden" name="message_change-status" value="{{ __('messages.message_change-status_dynamic', ['entity' => ':entity']) }}">
  <input type="hidden" name="message_delete" value="{{ __('messages.message_delete_dynamic', ['entity' => ':entity']) }}">
  <input type="hidden" name="message_restore" value="{{ __('messages.message_restore_dynamic', ['entity' => ':entity']) }}">
  <input type="hidden" name="message_permanently-delete" value="{{ __('messages.message_permanently-delete_dynamic', ['entity' => ':entity']) }}">
  
  <!-- Pass entity names for JavaScript dynamic pluralization -->
  <input type="hidden" name="entity_name" value="{{ $entityName }}">
  <input type="hidden" name="entity_name_plural" value="{{ $entityNamePlural }}">
  <button class="btn btn-primary" id="quick-action-apply">{{ __('messages.apply') }}</button>
</form>
