# SMS Delivery Automation (Android + ADB)

This project sends SMS messages from a CSV file by automating the Android SMS app UI over ADB.

Current script file:
- sms-delivery.py

Current contacts file:
- contacts.csv

## What This Script Does

The script:
- Reads phone numbers and messages from CSV.
- Opens Microsoft SMS Organizer compose screen for each contact.
- Ensures SIM 2 is selected (dual-SIM flow).
- Finds and taps the Send button automatically.

## Prerequisites

1. Linux machine with Python 3.8+.
2. Android device connected over USB.
3. Developer options enabled on phone.
4. USB debugging enabled on phone.
5. ADB platform-tools installed.
6. Microsoft SMS Organizer installed and set as default SMS app.

## Verify Device Connection

Run:

```bash
/home/santneel/Downloads/platform-tools-latest-linux/platform-tools/adb devices
```

Expected output should show your device in "device" state.

## Project Files

- sms-delivery.py: main automation script.
- contacts.csv: input contact list.

## CSV Format

Use this exact header:

```csv
name,phone,message
Sanjay,9820727797,Jai Shree Krishna
Suraj,9373086353,Jai Shree Ram
```

Notes:
- Keep column names exactly as: name, phone, message.
- Do not add commas inside message text unless you quote the field.

## Script Configuration

Open sms-delivery.py and review these constants near the top:

- ADB_PATH: full path to adb binary.
- SMS_COMPONENT: compose activity for SMS Organizer.
- PREFERRED_SIM_LABEL: set to "2" for SIM 2 (set "1" for SIM 1).
- SIM_SWITCH_RETRIES: retry count for SIM selection.
- SEND_TAP_RETRIES: retry count for send button detection/tap.

If your ADB path is different, update ADB_PATH first.

## How To Run

From project directory:

```bash
python3 sms-delivery.py
```

The script prints per-contact status:
- Compose screen opened
- SIM selection status
- Send tap attempt result

## Dual SIM Behavior

The script enforces SIM 2 before each send by reading the SIM label in the compose UI.

To always use SIM 1 instead, change:

```python
PREFERRED_SIM_LABEL = "1"
```

## Recommended Phone State Before Running

- Keep phone unlocked.
- Keep screen on.
- Do not manually switch apps while script is running.
- Keep SMS Organizer updated.

## Troubleshooting

### 1) "Unsupported argument: Shree"

Cause:
- Message text with spaces was split by shell parsing.

Fix:
- Already handled in current script using safe quoting.

### 2) "Send button not found"

Try:
- Open SMS Organizer manually once.
- Increase SEND_TAP_RETRIES.
- Keep screen awake and avoid overlays/popups.
- Re-run the script.

### 3) Messages not sending

Check:
- SIM has active SMS service.
- SMS app permissions are granted.
- Mobile network signal is available.
- Airplane mode is off.

### 4) ADB command not found

Either:
- Update ADB_PATH in script, or
- Install platform-tools and use the correct path.

## Safety Notes

- This is UI automation, not a direct telephony API integration.
- App UI updates can break selectors; if that happens, re-detect UI element IDs/bounds.
- Review your contacts.csv before running to avoid unintended SMS sends.

## Quick Run Checklist

1. Device connected and visible in adb devices.
2. SMS Organizer is default SMS app.
3. contacts.csv has correct data.
4. ADB_PATH is correct.
5. Run: python3 sms-delivery.py
