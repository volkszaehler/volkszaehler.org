--
-- PostgreSQL database dump
--

SET client_encoding = 'UTF8';
SET standard_conforming_strings = off;
SET check_function_bodies = false;
SET client_min_messages = warning;
SET escape_string_warning = off;

SET search_path = public, pg_catalog;

SET default_tablespace = '';

SET default_with_oids = false;

--
-- Name: channels; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE channels (
    function character(100),
    resolution INTEGER NOT NULL DEFAULT 1000,
    id integer NOT NULL,
    uuid uuid NOT NULL,
    CONSTRAINT channels_id PRIMARY KEY (id)
);

CREATE UNIQUE INDEX channels_uuid ON channels (uuid);

COMMENT ON COLUMN channels.function IS 'description of what this meter is used for';
COMMENT ON COLUMN channels.resolution IS 'resolution of power meter [pulses/kWh]';


--
-- Name: pulses; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE pulses (
    servertime timestamp without time zone NOT NULL,
    controllertime timestamp without time zone NOT NULL,
    channel_id integer NOT NULL REFERENCES channels(id) ON DELETE CASCADE,
    time_delta real
);

-- solange nach servertime gesucht wird
CREATE INDEX pulses_servertime ON pulses (servertime);


COMMENT ON COLUMN pulses.time_delta IS 'difference between controllertime and servertime';


REVOKE ALL ON SCHEMA public FROM PUBLIC;
REVOKE ALL ON SCHEMA public FROM postgres;
GRANT ALL ON SCHEMA public TO postgres;
GRANT USAGE ON SCHEMA public TO smreader;
GRANT USAGE ON SCHEMA public TO smlogger;


--
-- Name: channels; Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON TABLE channels FROM PUBLIC;
REVOKE ALL ON TABLE channels FROM postgres;
GRANT ALL ON TABLE channels TO postgres;
GRANT SELECT ON TABLE channels TO smlogger;
GRANT SELECT ON TABLE channels TO smreader;


--
-- Name: pulses; Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON TABLE pulses FROM PUBLIC;
REVOKE ALL ON TABLE pulses FROM postgres;
GRANT ALL ON TABLE pulses TO postgres;
GRANT INSERT ON TABLE pulses TO smlogger;
GRANT SELECT ON TABLE pulses TO smreader;


--
-- PostgreSQL database dump complete
--
