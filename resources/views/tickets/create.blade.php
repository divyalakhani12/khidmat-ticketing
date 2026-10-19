<!DOCTYPE html>
<html>
<head>
    <title>Zindagi Trust IT Support</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f6f8;
            margin: 0;
        }

        .container {
            max-width: 750px;
            margin: 40px auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
        }

        h1 {
            margin-bottom: 5px;
        }

        label {
            display: block;
            margin-top: 18px;
            font-weight: bold;
        }

        input, select, textarea {
            width: 100%;
            padding: 10px;
            margin-top: 6px;
            box-sizing: border-box;
        }

        textarea {
            min-height: 140px;
        }

        button {
            margin-top: 25px;
            padding: 12px 20px;
            cursor: pointer;
        }

        .error {
            color: red;
        }
    </style>
</head>

<body>

<div class="container">

    <h1>IT Support Request</h1>
    <p>Submit an IT issue or service request.</p>

    @if ($errors->any())
        <div class="error">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('tickets.store') }}" enctype="multipart/form-data">

        @csrf

        <label>Name</label>
        <input type="text" name="requester_name"
               value="{{ old('requester_name') }}" required>

        <label>Email Address</label>
        <input type="email" name="requester_email"
               value="{{ old('requester_email') }}" required>

        <label>Contact Number</label>
        <input type="text" name="contact_number"
               value="{{ old('contact_number') }}">

        <label>Branch / Location</label>
        <select name="branch_id" required>
            <option value="">Select branch</option>

            @foreach ($branches as $branch)
                <option value="{{ $branch->id }}"
                    @selected(old('branch_id') == $branch->id)>
                    {{ $branch->name }}
                </option>
            @endforeach
        </select>

        <label>Department</label>
        <select name="department_id">
            <option value="">Select department</option>

            @foreach ($departments as $department)
                <option value="{{ $department->id }}"
                    @selected(old('department_id') == $department->id)>
                    {{ $department->name }}
                </option>
            @endforeach
        </select>

        <label>Issue Category</label>
        <select name="category_id" required>
            <option value="">Select category</option>

            @foreach ($categories as $category)
                <option value="{{ $category->id }}"
                    @selected(old('category_id') == $category->id)>
                    {{ $category->name }}
                </option>
            @endforeach
        </select>

        <label>Priority</label>
        <select name="priority" required>
            <option value="Low">Low</option>
            <option value="Medium" selected>Medium</option>
            <option value="High">High</option>
            <option value="Critical">Critical</option>
        </select>

        <label>Subject</label>
        <input type="text" name="subject"
               value="{{ old('subject') }}" required>

        <label>Description</label>
        <textarea name="description" required>{{ old('description') }}</textarea>

        <label>Attachments (optional)</label>
        <input type="file" name="attachments[]" multiple>
        <small>Up to 5 files, 5 MB each. JPG, PNG, PDF, DOCX, XLSX or TXT.</small>
        
        <button type="submit">
            Submit Ticket
        </button>

    </form>

</div>

</body>
</html>