import os

def update_notif_icon(path):
    if not os.path.exists(path):
        print(f"Skipping {path}, not found.")
        return
    with open(path, 'r', encoding='utf-8') as f:
        content = f.read()
    
    # Target the notification bell container
    old_pattern = '<div class="position-relative p-2 rounded-circle" style="cursor:pointer;background:var(--bg-card);border:1px solid var(--border);" data-bs-toggle="dropdown">'
    new_pattern = '<div class="position-relative" style="cursor:pointer" data-bs-toggle="dropdown">'
    
    if old_pattern in content:
        new_content = content.replace(old_pattern, new_pattern)
        with open(path, 'w', encoding='utf-8') as f:
            f.write(new_content)
        print(f"Updated {path}")
    else:
        # Fallback for minor variations in spacing/quoting
        print(f"Pattern not found in {path}. Trying a more flexible match.")
        import re
        flex_pattern = r'<div class="position-relative p-2 rounded-circle" style="cursor:pointer;background:var\(--bg-card\);border:1px solid var\(--border\);" data-bs-toggle="dropdown">'
        new_content = re.sub(flex_pattern, new_pattern, content)
        if new_content != content:
            with open(path, 'w', encoding='utf-8') as f:
                f.write(new_content)
            print(f"Updated {path} using regex")
        else:
             print(f"Failed to update {path}")

update_notif_icon(r'C:\xampp\htdocs\SCC_Frontend\faculty\my_submissions.php')
update_notif_icon(r'C:\xampp\htdocs\SCC_Frontend\dept_head\my_submissions.php')
update_notif_icon(r'C:\xampp\htdocs\SCC_Frontend\dept_head\syllabus_review.php')
