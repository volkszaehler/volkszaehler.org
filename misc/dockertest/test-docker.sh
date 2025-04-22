#!/bin/bash
set -e
docker compose up -d
CHANNEL_JSON=$(curl --retry 10 --retry-all-errors --no-progress-meter -X POST \
    -d "type=electric+meter&title=Test&resolution=1&public=1" \
    "http://localhost:8080/channel.json")
echo $CHANNEL_JSON
CHANNEL=$(echo $CHANNEL_JSON | jq --raw-output '.entity.uuid')
curl --no-progress-meter -X POST -d "ts=1284677961150&value=10" \
    "http://localhost:8080/data/$CHANNEL.json"
echo
curl --no-progress-meter -X POST -d "ts=1284677961151&value=20" \
    "http://localhost:8080/data/$CHANNEL.json"
echo
DATA=$(curl --no-progress-meter \
    "http://localhost:8080/data/$CHANNEL.json?from=01-09-2010&to=01-10-2010")
echo $DATA
ROWS=$(echo $DATA | jq '.data.rows')
if [ "$ROWS" != "2" ]
then
    echo "send 2 values, but got $ROWS"
    exit 1
else
    echo "send 2 values and got them back"
fi

