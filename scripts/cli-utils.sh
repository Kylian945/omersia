#!/bin/bash
# ========================================
# Omersia CLI Utilities Library
# ========================================
# Premium CLI functions for animations,
# spinners, progress bars, boxes, and more
# ========================================

# ========================================
# OS & Terminal Detection
# ========================================

# Detect OS type
OS_TYPE="unknown"
case "$OSTYPE" in
    darwin*)  OS_TYPE="macos" ;;
    linux*)   OS_TYPE="linux" ;;
    msys*)    OS_TYPE="windows" ;;
    cygwin*)  OS_TYPE="windows" ;;
    *)        OS_TYPE="unknown" ;;
esac

# Detect terminal capability for Unicode
UNICODE_SUPPORT=true
if [[ "$TERM" == "dumb" ]] || [[ "$OS_TYPE" == "windows" && ! -n "$WSL_DISTRO_NAME" ]]; then
    UNICODE_SUPPORT=false
fi

# Cross-platform sed function
# Usage: sed_inplace 's/foo/bar/g' file.txt
sed_inplace() {
    local expression="$1"
    local file="$2"

    if [[ "$OS_TYPE" == "macos" ]]; then
        # macOS (BSD sed) requires backup extension
        sed -i.bak "$expression" "$file"
        rm -f "${file}.bak"
    else
        # Linux (GNU sed)
        sed -i "$expression" "$file"
    fi
}

# ========================================
# Colors & Styles
# ========================================

# Basic colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[0;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
WHITE='\033[0;37m'
GRAY='\033[0;90m'

# Bright colors
BRIGHT_RED='\033[91m'
BRIGHT_GREEN='\033[92m'
BRIGHT_YELLOW='\033[93m'
BRIGHT_BLUE='\033[94m'
BRIGHT_MAGENTA='\033[95m'
BRIGHT_CYAN='\033[96m'
BRIGHT_WHITE='\033[97m'

# Styles
RESET='\033[0m'
BOLD='\033[1m'
DIM='\033[2m'
UNDERLINE='\033[4m'
BLINK='\033[5m'
REVERSE='\033[7m'
HIDDEN='\033[8m'

# Background colors
BG_BLACK='\033[40m'
BG_RED='\033[41m'
BG_GREEN='\033[42m'
BG_YELLOW='\033[43m'
BG_BLUE='\033[44m'
BG_MAGENTA='\033[45m'
BG_CYAN='\033[46m'
BG_WHITE='\033[47m'

# ========================================
# Icons
# ========================================

# Use Unicode icons if supported, fallback to ASCII
if [[ "$UNICODE_SUPPORT" == "true" ]]; then
    ICON_SUCCESS="✅"
    ICON_ERROR="❌"
    ICON_WARNING="⚠️"
    ICON_INFO="ℹ️"
    ICON_ROCKET="🚀"
    ICON_PACKAGE="📦"
    ICON_TEST="🧪"
    ICON_SEARCH="🔍"
    ICON_BUILD="🏗️"
    ICON_CLEAN="🧹"
    ICON_LINT="🔧"
    ICON_DEV="💻"
    ICON_CLOCK="⏱️"
    ICON_CHECK="✔️"
    ICON_CROSS="✖️"
    ICON_ARROW="➜"
    ICON_STAR="⭐"
    ICON_FIRE="🔥"
    ICON_LOCK="🔒"
    ICON_KEY="🔑"
    ICON_DATABASE="🗄️"
    ICON_DOCKER="🐳"
    ICON_SPARKLES="✨"
    ICON_HOURGLASS="⏳"
    ICON_GEAR="⚙️"
    ICON_SHIELD="🛡️"
else
    # ASCII fallback for terminals without Unicode support
    ICON_SUCCESS="[OK]"
    ICON_ERROR="[ERR]"
    ICON_WARNING="[!]"
    ICON_INFO="[i]"
    ICON_ROCKET="[>>]"
    ICON_PACKAGE="[*]"
    ICON_TEST="[T]"
    ICON_SEARCH="[?]"
    ICON_BUILD="[#]"
    ICON_CLEAN="[~]"
    ICON_LINT="[+]"
    ICON_DEV="[>]"
    ICON_CLOCK="[@]"
    ICON_CHECK="[v]"
    ICON_CROSS="[x]"
    ICON_ARROW="=>"
    ICON_STAR="[*]"
    ICON_FIRE="[!]"
    ICON_LOCK="[L]"
    ICON_KEY="[K]"
    ICON_DATABASE="[D]"
    ICON_DOCKER="[D]"
    ICON_SPARKLES="[*]"
    ICON_HOURGLASS="[.]"
    ICON_GEAR="[o]"
    ICON_SHIELD="[S]"
fi

# ========================================
# Spinner Animation
# ========================================

# Spinner frames (Unicode Braille or ASCII fallback)
if [[ "$UNICODE_SUPPORT" == "true" ]]; then
    SPINNER_FRAMES=("⠋" "⠙" "⠹" "⠸" "⠼" "⠴" "⠦" "⠧" "⠇" "⠏")
else
    SPINNER_FRAMES=("|" "/" "-" "\\" "|" "/" "-" "\\")
fi
SPINNER_PID=""

# Show animated spinner
# Usage: show_spinner "Loading..." &
#        SPINNER_PID=$!
show_spinner() {
    local message="${1:-Loading...}"
    local i=0

    # Hide cursor
    tput civis 2>/dev/null || true

    while true; do
        printf "\r${CYAN}${SPINNER_FRAMES[$i]}${RESET} ${message}"
        i=$(((i + 1) % ${#SPINNER_FRAMES[@]}))
        sleep 0.1
    done
}

# Stop spinner and show result
# Usage: stop_spinner $SPINNER_PID "success" "Done!"
#        stop_spinner $SPINNER_PID "error" "Failed!"
stop_spinner() {
    local pid=$1
    local result_type=$2
    local message=$3

    if [ -n "$pid" ]; then
        kill "$pid" 2>/dev/null || true
        wait "$pid" 2>/dev/null || true
    fi

    # Clear the line
    printf "\r\033[K"

    # Show cursor
    tput cnorm 2>/dev/null || true

    # Print result
    case "$result_type" in
        "success")
            echo -e "${GREEN}${ICON_SUCCESS}${RESET} ${message}"
            ;;
        "error")
            echo -e "${RED}${ICON_ERROR}${RESET} ${message}"
            ;;
        "warning")
            echo -e "${YELLOW}${ICON_WARNING}${RESET} ${message}"
            ;;
        *)
            echo -e "${message}"
            ;;
    esac
}

# ========================================
# Progress Bar
# ========================================

# Show progress bar
# Usage: progress_bar 60 "Installing dependencies..."
progress_bar() {
    local percent=$1
    local message="${2:-Processing...}"
    local bar_length=40
    local filled=$((percent * bar_length / 100))
    local empty=$((bar_length - filled))

    printf "\r${CYAN}["
    printf "%${filled}s" | tr ' ' '█'
    printf "%${empty}s" | tr ' ' '░'
    printf "]${RESET} ${BOLD}%3d%%${RESET} ${message}" "$percent"
}

# Complete progress bar
complete_progress_bar() {
    local message="${1:-Complete!}"
    printf "\r${GREEN}["
    printf "%40s" | tr ' ' '█'
    printf "]${RESET} ${BOLD}100%%${RESET} ${GREEN}${message}${RESET}\n"
}

# ========================================
# Box Drawing
# ========================================

# Print a box around text
# Usage: print_box "Title" "Content line 1" "Content line 2"
print_box() {
    local title="$1"
    shift
    local lines=("$@")

    # Calculate max width
    local max_width=${#title}
    for line in "${lines[@]}"; do
        if [ ${#line} -gt $max_width ]; then
            max_width=${#line}
        fi
    done

    local box_width=$((max_width + 4))

    # Top border
    echo -e "${CYAN}╭$(printf '─%.0s' $(seq 1 $box_width))╮${RESET}"

    # Title
    local title_padding=$(((box_width - ${#title}) / 2))
    printf "${CYAN}│${RESET}%*s${BOLD}${BRIGHT_CYAN}%s${RESET}%*s${CYAN}│${RESET}\n" \
        $title_padding "" "$title" $((box_width - title_padding - ${#title})) ""

    # Separator
    if [ ${#lines[@]} -gt 0 ]; then
        echo -e "${CYAN}├$(printf '─%.0s' $(seq 1 $box_width))┤${RESET}"
    fi

    # Content lines
    for line in "${lines[@]}"; do
        printf "${CYAN}│${RESET} %-${max_width}s ${CYAN}│${RESET}\n" "$line"
    done

    # Bottom border
    echo -e "${CYAN}╰$(printf '─%.0s' $(seq 1 $box_width))╯${RESET}"
}

# Print info box
print_info_box() {
    local title="$1"
    shift
    echo ""
    echo -e "${BLUE}╭──────────────────────────────────────────────────╮${RESET}"
    echo -e "${BLUE}│${RESET} ${ICON_INFO} ${BOLD}${title}${RESET}"
    echo -e "${BLUE}├──────────────────────────────────────────────────┤${RESET}"
    for line in "$@"; do
        echo -e "${BLUE}│${RESET}   ${line}"
    done
    echo -e "${BLUE}╰──────────────────────────────────────────────────╯${RESET}"
    echo ""
}

# Print success box
print_success_box() {
    local title="$1"
    shift
    echo ""
    echo -e "${GREEN}╭──────────────────────────────────────────────────╮${RESET}"
    echo -e "${GREEN}│${RESET} ${ICON_SUCCESS} ${BOLD}${title}${RESET}"
    echo -e "${GREEN}├──────────────────────────────────────────────────┤${RESET}"
    for line in "$@"; do
        echo -e "${GREEN}│${RESET}   ${line}"
    done
    echo -e "${GREEN}╰──────────────────────────────────────────────────╯${RESET}"
    echo ""
}

# Print warning box
print_warning_box() {
    local title="$1"
    shift
    echo ""
    echo -e "${YELLOW}╭──────────────────────────────────────────────────╮${RESET}"
    echo -e "${YELLOW}│${RESET} ${ICON_WARNING} ${BOLD}${title}${RESET}"
    echo -e "${YELLOW}├──────────────────────────────────────────────────┤${RESET}"
    for line in "$@"; do
        echo -e "${YELLOW}│${RESET}   ${line}"
    done
    echo -e "${YELLOW}╰──────────────────────────────────────────────────╯${RESET}"
    echo ""
}

# Print error box
print_error_box() {
    local title="$1"
    shift
    echo ""
    echo -e "${RED}╭──────────────────────────────────────────────────╮${RESET}"
    echo -e "${RED}│${RESET} ${ICON_ERROR} ${BOLD}${title}${RESET}"
    echo -e "${RED}├──────────────────────────────────────────────────┤${RESET}"
    for line in "$@"; do
        echo -e "${RED}│${RESET}   ${line}"
    done
    echo -e "${RED}╰──────────────────────────────────────────────────╯${RESET}"
    echo ""
}

# ========================================
# Separators
# ========================================

# Print gradient separator
print_separator() {
    local width=${1:-60}
    echo -e "${CYAN}$(printf '━%.0s' $(seq 1 $width))${RESET}"
}

# Print double separator
print_double_separator() {
    local width=${1:-60}
    echo -e "${CYAN}$(printf '═%.0s' $(seq 1 $width))${RESET}"
}

# ========================================
# Banner
# ========================================

# Print Omersia banner
print_banner() {
    echo ""
    echo -e "${CYAN}╔════════════════════════════════════════════════════════════════════════╗${RESET}"
    echo -e "${CYAN}║${RESET}                                                                        ${CYAN}║${RESET}"
    echo -e "${CYAN}║${RESET} ${BRIGHT_CYAN}      ██████  ███    ███  ███████  ██████   ███████  ██   █████${RESET}        ${CYAN}║${RESET}"
    echo -e "${CYAN}║${RESET} ${BRIGHT_CYAN}     ██    ██ ████  ████  ██       ██   ██  ██       ██  ██   ██${RESET}       ${CYAN}║${RESET}"
    echo -e "${CYAN}║${RESET} ${BRIGHT_CYAN}     ██    ██ ██ ████ ██  █████    ██████   ███████  ██  ███████${RESET}       ${CYAN}║${RESET}"
    echo -e "${CYAN}║${RESET} ${BRIGHT_CYAN}     ██    ██ ██  ██  ██  ██       ██   ██       ██  ██  ██   ██${RESET}       ${CYAN}║${RESET}"
    echo -e "${CYAN}║${RESET} ${BRIGHT_CYAN}      ██████  ██      ██  ███████  ██   ██  ███████  ██  ██   ██${RESET}       ${CYAN}║${RESET}"
    echo -e "${CYAN}║${RESET}                                                                        ${CYAN}║${RESET}"
    echo -e "${CYAN}║${RESET}                ${DIM}E-Commerce Platform${RESET} ${GRAY}·${RESET} ${DIM}Version 1.0.0${RESET}                     ${CYAN}║${RESET}"
    echo -e "${CYAN}║${RESET}                                                                        ${CYAN}║${RESET}"
    echo -e "${CYAN}╚════════════════════════════════════════════════════════════════════════╝${RESET}"
    echo ""
}

# ========================================
# Fancy Messages
# ========================================

# Print step with icon
print_step_fancy() {
    local step_num="$1"
    local total_steps="$2"
    local title="$3"
    local icon="${4:-${ICON_ARROW}}"

    echo ""
    echo -e "${BRIGHT_BLUE}${icon} ${BOLD}[Step ${step_num}/${total_steps}]${RESET} ${BRIGHT_CYAN}${title}${RESET}"
    print_separator 60
}

# Print success message
print_success() {
    echo -e "${GREEN}${ICON_SUCCESS}${RESET} $1"
}

# Print error message
print_error() {
    echo -e "${RED}${ICON_ERROR}${RESET} $1"
}

# Print warning message
print_warning() {
    echo -e "${YELLOW}${ICON_WARNING}${RESET} $1"
}

# Print info message
print_info() {
    echo -e "${CYAN}${ICON_INFO}${RESET} $1"
}

# ========================================
# Animations
# ========================================

# Animate dots
# Usage: animate_dots 3 "Loading"
animate_dots() {
    local count=${1:-3}
    local message="${2:-Loading}"

    for i in $(seq 1 $count); do
        printf "\r${CYAN}${message}$(printf '.%.0s' $(seq 1 $i))${RESET}   "
        sleep 0.3
    done
    printf "\r\033[K"
}

# Typewriter effect
# Usage: typewriter_effect "Hello World"
typewriter_effect() {
    local text="$1"
    local delay=${2:-0.05}

    for ((i=0; i<${#text}; i++)); do
        printf "%s" "${text:$i:1}"
        sleep $delay
    done
    echo ""
}

# ========================================
# Utility Functions
# ========================================

# Wait with countdown
# Usage: wait_with_countdown 5 "Starting in"
wait_with_countdown() {
    local seconds=$1
    local message="${2:-Wait}"

    for ((i=seconds; i>0; i--)); do
        printf "\r${YELLOW}${ICON_HOURGLASS} ${message} ${i}s...${RESET}"
        sleep 1
    done
    printf "\r\033[K"
}

# Print header
print_header() {
    echo ""
    print_double_separator 60
    echo -e "${BOLD}${BRIGHT_CYAN}$1${RESET}"
    print_double_separator 60
    echo ""
}

# Print subheader
print_subheader() {
    echo ""
    echo -e "${BOLD}${BRIGHT_BLUE}$1${RESET}"
    print_separator 60
    echo ""
}
