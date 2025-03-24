tz: tzdata.zi leapseconds
	zic -d tz -L ./leapseconds ./tzdata.zi

tzdata.zi:
	wget -O tzdata.zi https://data.iana.org/time-zones/tzdb/tzdata.zi

leapseconds:
	wget -O leapseconds https://data.iana.org/time-zones/tzdb/leapseconds
