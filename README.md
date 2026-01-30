# Moodle Learning Path Block (`block_learningpaths`)

This plugin is the frontend visualization component for the **Local Admin Panel** learning path system. It displays a progressive, gamified list of courses assigned to the user, managing access control, visual status (locked/completed), and auto-enrolment.

## 📋 Features

*   **Visual Progress Tracking:** Users can see their learning path as a timeline of cards.
*   **State Indication:** Clearly distinguishes between:
    *   ✅ **Completed:** Finished courses (Green check).
    *   🔓 **Current:** Active/Next available courses (Blue play button + Highlight).
    *   🔒 **Locked:** Future courses that require prerequisites (Greyed out).
*   **Smart Logic Support:**
    *   **Sequential (AND):** Courses must be completed one by one.
    *   **Parallel (OR):** Multiple courses become available simultaneously within the same step.
*   **Auto-Enrolment ("Gatekeeper"):** Users are automatically enrolled in the course when they click the card (if eligible), removing the need for manual enrolment by admins.
*   **Security Observer:** Prevents unauthorized access via direct URL manipulation if the previous steps in the path are not completed.

## ⚠️ Dependencies

This plugin **requires** the following backend plugin to function:

*   **[local_adminpanel](../local/adminpanel)**: Provides the database schema, administrative interface, and the core logic Trait (`path_logic`).

> **Note:** The block will fail to load if `local_adminpanel` is not installed.

## 🚀 Installation

1.  Ensure `local_adminpanel` is installed in `your-moodle/local/adminpanel`.
2.  Copy this directory to `your-moodle/blocks/learningpaths`.
3.  Log in to your Moodle site as an administrator.
4.  Go to **Site administration > Notifications** to trigger the installation.

## ⚙️ Configuration & Usage

### Adding the Block
1.  Go to your **Dashboard** (or any course page).
2.  Turn editing on.
3.  Click "Add a block" and select **"My Learning Path"**.

### Permissions
The plugin defines the following capabilities:
*   `block/learningpaths:myaddinstance`: Allow adding the block to the Dashboard.
*   `block/learningpaths:addinstance`: Allow adding the block to course pages.

## 🧠 Logic & Architecture

### The "AND" vs "OR" Logic
The block renders courses based on groups calculated in the backend:
1.  **AND (Sequential):** Starts a new logical step. The previous step must be fully completed to unlock this one.
2.  **OR (Parallel):** Appends the course to the current step. If multiple courses are in the same step, they are all unlocked simultaneously when the previous step is done.

### The Security Flow
1.  **Visualization:** The block uses `main_view.mustache` to render cards. Locked courses link to `#`, while active courses link to the Gatekeeper script.
2.  **Gatekeeper (`access_course.php`):**
    *   Validates if the user is allowed to enter (Path Logic).
    *   Checks if the user is enrolled.
    *   **Auto-enrols** the user via the `manual` plugin if necessary (System level action).
    *   Redirects to the course.
3.  **Observer (`classes/observer.php`):**
    *   Listens for `\core\event\course_viewed`.
    *   If a user tries to access a restricted course via direct URL manipulation, they are redirected to the home page with a warning.

## 📂 File Structure

```text
blocks/learningpaths/
├── block_learningpaths.php  # Main block class (Uses path_logic trait)
├── db/
│   └── access.php           # Capability definitions
├── lang/
│   ├── en/                  # English strings
│   └── es/                  # Spanish strings
├── templates/
│   └── main_view.mustache   # Card-based UI template
└── version.php              # Plugin versioning
```

## 📜 License

Licensed under the [GNU General Public License (GPL) v3](http://www.gnu.org/copyleft/gpl.html) or later.
