@props([
    'name' => '',
    'id' => '',
    'inputId' => '',
    'placeholder' => 'Select an option',
    'options' => [], // Array or collection of key-value pairs or array items
    'selected' => '',
    'required' => false,
    'form' => null,
    'class' => '',
    'triggerClass' => '',
])

@php
    $uuid = $id ?: 'select-' . uniqid();
    $hiddenInputId = $inputId ?: '';
    
    // Normalize options into a uniform array format
    $normalizedOptions = [];
    foreach ($options as $key => $value) {
        if (is_array($value) || is_object($value)) {
            $valueArray = (array)$value;
            $normalizedOptions[] = [
                'value' => $valueArray['value'] ?? $valueArray['id'] ?? $key,
                'label' => $valueArray['label'] ?? $valueArray['name'] ?? $valueArray['text'] ?? $valueArray['business_name'] ?? $key,
            ];
        } else {
            $normalizedOptions[] = [
                'value' => $key,
                'label' => $value,
            ];
        }
    }

    // Find initially selected label
    $selectedLabel = $placeholder;
    foreach ($normalizedOptions as $opt) {
        if ((string)$opt['value'] === (string)$selected) {
            $selectedLabel = $opt['label'];
            break;
        }
    }
@endphp

<div class="relative custom-combobox {{ $class }}" id="{{ $uuid }}">
    {{-- Hidden input to hold the actual selected value for form submission --}}
    <input type="hidden" 
           @if($hiddenInputId) id="{{ $hiddenInputId }}" @endif
           name="{{ $name }}" 
           value="{{ $selected }}" 
           @if($required) required @endif
           @if($form) form="{{ $form }}" @endif
           class="combobox-hidden-input">

    <button type="button" class="combobox-trigger w-full px-3 py-2 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg outline-none focus:border-indigo-500 transition text-slate-700 dark:text-slate-200 flex justify-between items-center cursor-pointer {{ $triggerClass }}">
        <span class="combobox-label truncate">{{ $selectedLabel }}</span>
        <svg class="w-3 h-3 text-slate-400 shrink-0 ml-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
        </svg>
    </button>
    <div class="combobox-dropdown hidden absolute z-50 left-0 right-0 mt-1 p-2 bg-white dark:bg-slate-950 border border-slate-200/80 dark:border-slate-800/80 rounded-xl shadow-xl backdrop-blur-xl">
        <div class="relative mb-2">
            <span class="absolute inset-y-0 left-0 flex items-center pl-2 pointer-events-none text-slate-400">
                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </span>
            <input type="text" class="combobox-search w-full pl-7 pr-2.5 py-1.5 text-xs bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg outline-none focus:border-indigo-500 transition text-slate-700 dark:text-slate-200" placeholder="Search...">
        </div>
        <div class="combobox-options max-h-48 overflow-y-auto space-y-1">
            @if(!$required)
                <div class="combobox-option px-2.5 py-1.5 text-xs rounded-lg hover:bg-indigo-500/10 hover:text-indigo-600 dark:hover:text-indigo-400 cursor-pointer transition text-slate-700 dark:text-slate-300 font-medium" data-value="">{{ $placeholder }}</div>
            @endif
            @foreach($normalizedOptions as $opt)
                <div class="combobox-option px-2.5 py-1.5 text-xs rounded-lg hover:bg-indigo-500/10 hover:text-indigo-600 dark:hover:text-indigo-400 cursor-pointer transition text-slate-700 dark:text-slate-300 font-medium {{ (string)$opt['value'] === (string)$selected ? 'bg-indigo-500/10 text-indigo-600 dark:text-indigo-400' : '' }}" data-value="{{ $opt['value'] }}">{{ $opt['label'] }}</div>
            @endforeach
        </div>
    </div>
</div>
