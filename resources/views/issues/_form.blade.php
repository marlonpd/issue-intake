<label>
    Title
    <input type="text" name="title" value="{{ old('title', $issue->title) }}" maxlength="255" required>
    @error('title') <span class="error">{{ $message }}</span> @enderror
</label>

<label>
    Description
    <textarea name="description" rows="6" required>{{ old('description', $issue->description) }}</textarea>
    @error('description') <span class="error">{{ $message }}</span> @enderror
</label>

<div class="form-row">
    <label>
        Priority
        <select name="priority" required>
            @foreach (App\Enums\IssuePriority::cases() as $priority)
                <option value="{{ $priority->value }}" @selected(old('priority', $issue->priority?->value ?? 'medium') === $priority->value)>{{ Str::headline($priority->value) }}</option>
            @endforeach
        </select>
        @error('priority') <span class="error">{{ $message }}</span> @enderror
    </label>

    <label>
        Category
        <select name="category" required>
            @foreach (App\Enums\IssueCategory::cases() as $category)
                <option value="{{ $category->value }}" @selected(old('category', $issue->category?->value) === $category->value)>{{ Str::headline($category->value) }}</option>
            @endforeach
        </select>
        @error('category') <span class="error">{{ $message }}</span> @enderror
    </label>

    <label>
        Status
        <select name="status" required>
            @foreach (App\Enums\IssueStatus::cases() as $status)
                <option value="{{ $status->value }}" @selected(old('status', $issue->status?->value) === $status->value)>{{ Str::headline($status->value) }}</option>
            @endforeach
        </select>
        @error('status') <span class="error">{{ $message }}</span> @enderror
    </label>
</div>
