import csv
import re
import subprocess
import shlex
import time
import xml.etree.ElementTree as ET


ADB_PATH = "/home/santneel/Downloads/platform-tools-latest-linux/platform-tools/adb"
SMS_COMPONENT = "com.microsoft.android.smsorganizer/.NewMessageActivity"
PREFERRED_SIM_LABEL = "2"
SIM_SWITCH_RETRIES = 3
SEND_TAP_RETRIES = 3
SET_DEFAULT_MESSAGE = True
DEFAULT_MESSAGE = (
    "राष्ट्रीय स्वयं सेवक संघ खंड नाथद्वारा\n\n"
    "सभी स्वयंसेवक ओर कार्यकर्ता इस लिंक का उपयोग कर दी गई जानकारी गूगल फॉर्म के माध्ययम् से पूर्ण करे ।\n\n"
    "हर शाखा प्रत्येक स्वयंसेवक से  भरने का आग्रह करे ।\n\n"
    "https://bit.ly/rss-data-update"
)


def run_adb_shell(command):
    return subprocess.run(
        [ADB_PATH, "shell", command],
        capture_output=True,
        text=True,
        timeout=15,
    )


def dump_ui_xml():
    result = subprocess.run(
        [ADB_PATH, "exec-out", "uiautomator", "dump", "/dev/tty"],
        capture_output=True,
        text=True,
        timeout=15,
    )
    raw = f"{result.stdout}{result.stderr}"
    start = raw.find("<?xml")
    end = raw.rfind("</hierarchy>")
    if start == -1 or end == -1:
        return None
    return raw[start : end + len("</hierarchy>")]


def bounds_center(bounds):
    match = re.match(r"\[(\d+),(\d+)\]\[(\d+),(\d+)\]", bounds or "")
    if not match:
        return None
    x1, y1, x2, y2 = map(int, match.groups())
    return (x1 + x2) // 2, (y1 + y2) // 2


def find_send_button_center(xml_text):
    root = ET.fromstring(xml_text)
    nodes = list(root.iter("node"))

    # Primary selector verified on this device/app build.
    for node in nodes:
        resource_id = node.attrib.get("resource-id", "")
        if resource_id.endswith(":id/send_message_view") and node.attrib.get("enabled") == "true":
            center = bounds_center(node.attrib.get("bounds", ""))
            if center:
                return center

    # Fallback: any enabled, clickable send-like control on right side.
    for node in nodes:
        resource_id = node.attrib.get("resource-id", "").lower()
        content_desc = node.attrib.get("content-desc", "").lower()
        text = node.attrib.get("text", "").lower()
        clickable = node.attrib.get("clickable") == "true"
        enabled = node.attrib.get("enabled") == "true"
        if not (clickable and enabled):
            continue
        if "send" in resource_id or "send" in content_desc or text == "send":
            center = bounds_center(node.attrib.get("bounds", ""))
            if center and center[0] > 700:
                return center

    return None


def find_sim_label(xml_text):
    root = ET.fromstring(xml_text)
    for node in root.iter("node"):
        resource_id = node.attrib.get("resource-id", "")
        if resource_id.endswith(":id/sim_id_text_view"):
            label = node.attrib.get("text", "").strip()
            if label:
                return label
    return None


def find_sim_selector_center(xml_text):
    root = ET.fromstring(xml_text)
    nodes = list(root.iter("node"))
    for node in nodes:
        resource_id = node.attrib.get("resource-id", "")
        if resource_id.endswith(":id/send_sms_options_selection_container"):
            center = bounds_center(node.attrib.get("bounds", ""))
            if center:
                return center

    # Fallback for builds where only the image view is available in hierarchy.
    for node in nodes:
        resource_id = node.attrib.get("resource-id", "")
        if resource_id.endswith(":id/selected_send_sms_option_image_view"):
            center = bounds_center(node.attrib.get("bounds", ""))
            if center:
                return center

    return None


def ensure_preferred_sim(target_label=PREFERRED_SIM_LABEL, max_attempts=SIM_SWITCH_RETRIES):
    last_seen = None
    for _ in range(max_attempts):
        ui_xml = dump_ui_xml()
        if not ui_xml:
            time.sleep(0.6)
            continue

        last_seen = find_sim_label(ui_xml)
        if last_seen == target_label:
            return True, f"SIM {target_label} selected"

        selector = find_sim_selector_center(ui_xml)
        if not selector:
            return False, "SIM selector not found in UI"

        x, y = selector
        tap = run_adb_shell(f"input tap {x} {y}")
        if tap.returncode != 0:
            return False, f"SIM toggle tap failed: {(tap.stdout + tap.stderr).strip()}"

        time.sleep(0.9)

    # Final check after retries.
    ui_xml = dump_ui_xml()
    if ui_xml:
        last_seen = find_sim_label(ui_xml)
        if last_seen == target_label:
            return True, f"SIM {target_label} selected"

    return False, f"Could not switch to SIM {target_label}; current SIM label: {last_seen or 'unknown'}"


def tap_send_button(max_attempts=SEND_TAP_RETRIES):
    for _ in range(max_attempts):
        ui_xml = dump_ui_xml()
        if not ui_xml:
            time.sleep(0.6)
            continue

        center = find_send_button_center(ui_xml)
        if not center:
            time.sleep(0.6)
            continue

        x, y = center
        tap = run_adb_shell(f"input tap {x} {y}")
        if tap.returncode != 0:
            return False, f"Tap failed: {(tap.stdout + tap.stderr).strip()}"
        return True, f"Tapped send button at ({x}, {y})"

    return False, "Send button not found after retries"



def send_sms(phone, message):
    # IMPORTANT: quote message text so words like "Shree" are not parsed as extra args.
    remote_cmd = (
        "am start -W "
        f"-n {SMS_COMPONENT} "
        "-a android.intent.action.SENDTO "
        f"-d {shlex.quote(f'smsto:{phone}')} "
        f"--es sms_body {shlex.quote(message)}"
    )

    try:
        # Keep screen awake and dismiss lockscreen when possible so compose UI is visible.
        run_adb_shell("input keyevent KEYCODE_WAKEUP; wm dismiss-keyguard")

        result = run_adb_shell(remote_cmd)
        output = (result.stdout + result.stderr).strip()

        if result.returncode != 0 or "unable to resolve Intent" in output:
            print(f"Failed for {phone}: {output}")
            return

        print(f"Opened SMS Organizer compose screen for {phone}")
        time.sleep(1.5)

        sim_ok, sim_details = ensure_preferred_sim()
        if sim_ok:
            print(f"SIM selection: {sim_details}")
        else:
            print(f"SIM selection warning: {sim_details}")

        sent, details = tap_send_button()
        if sent:
            print(f"Auto-send attempted for {phone}: {details}")
        else:
            print(f"Auto-send failed for {phone}: {details}")
    except subprocess.TimeoutExpired:
        print("Timeout: ADB command took too long")
    except Exception as e:
        print(f"Error: {e}")

    time.sleep(2.5)

def main():
    with open('contacts.csv', newline='') as file:
        reader = csv.DictReader(file)
        for row in reader:
            phone = row['phone']
            # When enabled, always use script-level message and ignore CSV message column.
            message = DEFAULT_MESSAGE if SET_DEFAULT_MESSAGE else row['message']

            print(f"Sending SMS to {phone}")
            send_sms(phone, message)

if __name__ == "__main__":
    main()
