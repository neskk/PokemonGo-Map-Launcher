#!/bin/bash
#
# Licensed under the WTFPL: http://www.wtfpl.net/txt/copying/
#
# Checks a list of proxies for bans in PokemonGO and PTC.
# Proxies should be stored in proxies.txt, one per line.
#
# Proxies must be in one of the following formats:
# protocol://ip:port
# protocol://user:password@ip:port
#
# If you don't specify protocol, curl will normally use HTTP.
#
# Proxies are added to proxies_[good|bad|banned].txt based on
# whether they receive a 200 (good), 403/9 (banned) or other.
# You'll have the option to clear the files before it starts.
#
# Timeout is the maximum seconds for a check to complete.
# To change the time between proxy checks, adjust MaxDelay.
# In case of timeouts, curl will try again up to MaxRetries.
#
# Find me on Discord @Galactica#7178
# Found this script useful? Buy me a beer: https://paypal.me/dshoreman
#

Timeout=2
MaxDelay=3
MaxRetries=3
ProxyFile=proxies.txt

UserAgent="pokemongo/1 CFNetwork/758.5.3 Darwin/15.6.0"
PogoUrl="https://pgorelease.nianticlabs.com/plfe/version"
PtcUrl="https://sso.pokemon.com/sso/login?service=https%3A%2F%2Fsso.pokemon.com%2Fsso%2Foauth2.0%2FcallbackAuthorize"

if [ ! -f "$ProxyFile" ]; then
    echo "Could not find ${ProxyFile}!"

    read -r -p "Continue checking with external IP? [y/N] " response
    case "$response" in
        [yY]|[yY][eE][sS])
            SKIP_PROXY=true
            ProxyFile='noproxy'
            ;;
        *)
            echo "Aborting."
            exit
            ;;
    esac
else SKIP_PROXY=false; fi

if [ "$SKIP_PROXY" = true ]; then
    total=1
    longest=10
else
    for file in proxies_bad.txt proxies_banned.txt proxies_good.txt; do
        if [ -f $file ]; then
            read -rp "Remove ${file} before starting? [y/N] " response
            case "$response" in
                [yY]|[yY][eE][sS])
                    rm $file && echo "Successfully removed ${file}"
                    ;;
            esac
        fi
    done

    total=$(wc -l < "$ProxyFile")
    longest=$(wc -L < "$ProxyFile")
    ProxyFile=$(cat "$ProxyFile")
fi

echo
echo "Beginning PoGo/PTC ban check for ${total} proxies..."

declare -i pogo_fail=0
declare -i pogo_bans=0
declare -i ptc_fail=0
declare -i ptc_bans=0

printf "\n %-${longest}s   PoGo [ Status ]  PTC [ Status ]\n" 'Proxy'

for proxy in $ProxyFile; do
    proxy_param=''
    BAD=false
    BAN=false

    if [ "$SKIP_PROXY" = false ]; then
        proxy=$(echo "$proxy" | tr -d '\r')
        proxy_param=" -x $proxy"
    fi
    printf " %-${longest}s  " $proxy

    sleep $[($RANDOM % $MaxDelay) + 1]s

    # Begin PoGo check
    response=$(curl -sw 'HTTP_STATUS:%{http_code}' -m $Timeout --retry $MaxRetries -A "$UserAgent"$proxy_param $PogoUrl)
    exit_code=$?
    if [ $exit_code -eq 0 ]; then
        status=$(echo $response | tr -d '\n' | sed -e 's/.*HTTP_STATUS://')

        if [ $status -eq 403 ]; then BAN=true; pogo_bans+=1; echo -n ' BAN!'
        elif [ $status -eq 200 ]; then echo -n ' PASS'
        else BAD=true; pogo_fail+=1; echo -n ' FAIL'; fi

        echo -n " [HTTP:$status]"
    else BAD=true; pogo_fail+=1; printf " FAIL [CURL:%3s]" $exit_code; fi

    sleep $[($RANDOM % $MaxDelay) + 1]s

    # Begin PTC check
    response=$(curl -sw 'HTTP_STATUS:%{http_code}' -m $Timeout --retry $MaxRetries -A "$UserAgent"$proxy_param $PtcUrl)
    exit_code=$?
    if [ $exit_code -eq 0 ]; then
        status=$(echo $response | tr -d '\n' | sed -e 's/.*HTTP_STATUS://')

        if [ $status -eq 409 ]; then BAN=true; ptc_bans+=1; echo -n ' BAN!'
        elif [ $status -eq 200 ]; then echo -n ' PASS'
        else BAD=true; ptc_fail+=1; echo -n ' FAIL'; fi

        echo " [HTTP:${status}]"
    else BAD=true; ptc_fail+=1; printf " FAIL [CURL:%3s]\n" $exit_code; fi

    # Output to good/bad files
    if [ "$SKIP_PROXY" = false ]; then
        if $BAD; then
            echo "$proxy" >> proxies_bad.txt
        elif $BAN; then
            echo "$proxy" >> proxies_banned.txt
        else
            echo "$proxy" >> proxies_good.txt
        fi
    fi
done

echo
echo "Proxy check complete!"

if [ "$SKIP_PROXY" = true ]; then exit; fi

pogo_good=$((total - pogo_bans - pogo_fail))
ptc_good=$((total - ptc_bans - ptc_fail))

echo "PokemonGO has $pogo_bans bans, $pogo_good good and $pogo_fail other failures."
echo "PTC has $ptc_bans bans, $ptc_good good and $ptc_fail other failures."
