--
-- PostgreSQL database dump
--

-- Dumped from database version 17.4
-- Dumped by pg_dump version 17.4

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Name: allocation_status_type; Type: TYPE; Schema: public; Owner: postgres
--

CREATE TYPE public.allocation_status_type AS ENUM (
    'active',
    'inactive'
);


ALTER TYPE public.allocation_status_type OWNER TO postgres;

--
-- Name: asset_status_type; Type: TYPE; Schema: public; Owner: postgres
--

CREATE TYPE public.asset_status_type AS ENUM (
    'active',
    'inactive',
    'decommissioned'
);


ALTER TYPE public.asset_status_type OWNER TO postgres;

--
-- Name: ownership_type; Type: TYPE; Schema: public; Owner: postgres
--

CREATE TYPE public.ownership_type AS ENUM (
    'SGPL',
    'Rental',
    'BYOD'
);


ALTER TYPE public.ownership_type OWNER TO postgres;

--
-- Name: warranty_type; Type: TYPE; Schema: public; Owner: postgres
--

CREATE TYPE public.warranty_type AS ENUM (
    'Under Warranty',
    'NA',
    'Out of Warranty'
);


ALTER TYPE public.warranty_type OWNER TO postgres;

--
-- Name: update_updated_at_column(); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.update_updated_at_column() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$;


ALTER FUNCTION public.update_updated_at_column() OWNER TO postgres;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: allocated_asset_master; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.allocated_asset_master (
    id integer NOT NULL,
    asset_tag character varying(20) NOT NULL,
    user_email character varying(255) NOT NULL,
    assign_on timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    status public.allocation_status_type DEFAULT 'active'::public.allocation_status_type,
    end_date timestamp without time zone,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.allocated_asset_master OWNER TO postgres;

--
-- Name: allocated_asset_master_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.allocated_asset_master_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.allocated_asset_master_id_seq OWNER TO postgres;

--
-- Name: allocated_asset_master_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.allocated_asset_master_id_seq OWNED BY public.allocated_asset_master.id;


--
-- Name: approvals; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.approvals (
    id integer NOT NULL,
    memo_id integer,
    status character varying(20) DEFAULT 'pending'::character varying,
    comment text,
    approved_at timestamp without time zone,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    approved_by_email character varying(255),
    declined_by_email character varying(255),
    declined_by_name character varying(255),
    required_group_name character varying(255) NOT NULL,
    group_priority integer NOT NULL,
    approved_by_name character varying(255),
    CONSTRAINT approvals_status_check CHECK (((status)::text = ANY ((ARRAY['pending'::character varying, 'approved'::character varying, 'declined'::character varying])::text[])))
);


ALTER TABLE public.approvals OWNER TO postgres;

--
-- Name: approvals_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.approvals_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.approvals_id_seq OWNER TO postgres;

--
-- Name: approvals_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.approvals_id_seq OWNED BY public.approvals.id;


--
-- Name: asset_master; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.asset_master (
    id integer NOT NULL,
    type character varying(50) NOT NULL,
    ownership public.ownership_type NOT NULL,
    warranty public.warranty_type NOT NULL,
    warranty_start date,
    warranty_end date,
    serial_number character varying(30) NOT NULL,
    tag character varying(20) NOT NULL,
    model character varying(50) NOT NULL,
    location character varying(255) NOT NULL,
    status public.asset_status_type DEFAULT 'inactive'::public.asset_status_type,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.asset_master OWNER TO postgres;

--
-- Name: asset_master_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.asset_master_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.asset_master_id_seq OWNER TO postgres;

--
-- Name: asset_master_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.asset_master_id_seq OWNED BY public.asset_master.id;


--
-- Name: asset_type_master; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.asset_type_master (
    id integer NOT NULL,
    type character varying(50) NOT NULL,
    keyword character varying(10) NOT NULL,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.asset_type_master OWNER TO postgres;

--
-- Name: asset_type_master_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.asset_type_master_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.asset_type_master_id_seq OWNER TO postgres;

--
-- Name: asset_type_master_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.asset_type_master_id_seq OWNED BY public.asset_type_master.id;


--
-- Name: cache; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cache (
    key character varying(255) NOT NULL,
    value text NOT NULL,
    expiration integer NOT NULL
);


ALTER TABLE public.cache OWNER TO postgres;

--
-- Name: cache_locks; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cache_locks (
    key character varying(255) NOT NULL,
    owner character varying(255) NOT NULL,
    expiration integer NOT NULL
);


ALTER TABLE public.cache_locks OWNER TO postgres;

--
-- Name: candidate_skill_master; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.candidate_skill_master (
    id integer NOT NULL,
    skill_name character varying(50) NOT NULL,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.candidate_skill_master OWNER TO postgres;

--
-- Name: candidate_skill_master_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.candidate_skill_master_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.candidate_skill_master_id_seq OWNER TO postgres;

--
-- Name: candidate_skill_master_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.candidate_skill_master_id_seq OWNED BY public.candidate_skill_master.id;


--
-- Name: candidate_skills; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.candidate_skills (
    candidate_id integer NOT NULL,
    skill_id integer NOT NULL,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.candidate_skills OWNER TO postgres;

--
-- Name: candidate_source_master; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.candidate_source_master (
    id integer NOT NULL,
    source_name character varying(50) NOT NULL,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.candidate_source_master OWNER TO postgres;

--
-- Name: candidate_source_master_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.candidate_source_master_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.candidate_source_master_id_seq OWNER TO postgres;

--
-- Name: candidate_source_master_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.candidate_source_master_id_seq OWNED BY public.candidate_source_master.id;


--
-- Name: candidates_master; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.candidates_master (
    id integer NOT NULL,
    name character varying(100) NOT NULL,
    email character varying(100) NOT NULL,
    phone character varying(20) NOT NULL,
    source_id integer NOT NULL,
    resume_path character varying(255),
    current_status character varying(20) DEFAULT 'New'::character varying,
    notes text,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT candidates_master_current_status_check CHECK (((current_status)::text = ANY ((ARRAY['New'::character varying, 'Screening'::character varying, 'Interview'::character varying, 'Offered'::character varying, 'Hired'::character varying, 'Rejected'::character varying])::text[])))
);


ALTER TABLE public.candidates_master OWNER TO postgres;

--
-- Name: candidates_master_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.candidates_master_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.candidates_master_id_seq OWNER TO postgres;

--
-- Name: candidates_master_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.candidates_master_id_seq OWNED BY public.candidates_master.id;


--
-- Name: doc_tag; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.doc_tag (
    document_id integer NOT NULL,
    tag text NOT NULL
);


ALTER TABLE public.doc_tag OWNER TO postgres;

--
-- Name: failed_jobs; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.failed_jobs (
    id bigint NOT NULL,
    uuid character varying(255) NOT NULL,
    connection text NOT NULL,
    queue text NOT NULL,
    payload text NOT NULL,
    exception text NOT NULL,
    failed_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE public.failed_jobs OWNER TO postgres;

--
-- Name: failed_jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.failed_jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.failed_jobs_id_seq OWNER TO postgres;

--
-- Name: failed_jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.failed_jobs_id_seq OWNED BY public.failed_jobs.id;


--
-- Name: group_personalized_links; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.group_personalized_links (
    id integer NOT NULL,
    microsoft_group_name character varying(255) NOT NULL,
    link_name character varying(255) NOT NULL,
    link_url character varying(500) NOT NULL,
    link_logo character varying(255),
    background_color character varying(10) DEFAULT '#115948'::character varying,
    sort_order integer DEFAULT 1,
    replaces_link character varying(255),
    is_active boolean DEFAULT true,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.group_personalized_links OWNER TO postgres;

--
-- Name: group_personalized_links_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.group_personalized_links_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.group_personalized_links_id_seq OWNER TO postgres;

--
-- Name: group_personalized_links_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.group_personalized_links_id_seq OWNED BY public.group_personalized_links.id;


--
-- Name: groups; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.groups (
    id integer NOT NULL,
    name character varying(255) NOT NULL,
    priority integer DEFAULT 999 NOT NULL,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.groups OWNER TO postgres;

--
-- Name: groups_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.groups_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.groups_id_seq OWNER TO postgres;

--
-- Name: groups_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.groups_id_seq OWNED BY public.groups.id;


--
-- Name: job_batches; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.job_batches (
    id character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    total_jobs integer NOT NULL,
    pending_jobs integer NOT NULL,
    failed_jobs integer NOT NULL,
    failed_job_ids text NOT NULL,
    options text,
    cancelled_at integer,
    created_at integer NOT NULL,
    finished_at integer
);


ALTER TABLE public.job_batches OWNER TO postgres;

--
-- Name: jobs; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.jobs (
    id bigint NOT NULL,
    queue character varying(255) NOT NULL,
    payload text NOT NULL,
    attempts smallint NOT NULL,
    reserved_at integer,
    available_at integer NOT NULL,
    created_at integer NOT NULL
);


ALTER TABLE public.jobs OWNER TO postgres;

--
-- Name: jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.jobs_id_seq OWNER TO postgres;

--
-- Name: jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.jobs_id_seq OWNED BY public.jobs.id;


--
-- Name: jobs_master; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.jobs_master (
    id integer NOT NULL,
    job_title character varying(255) NOT NULL,
    department character varying(255) NOT NULL,
    location character varying(255) NOT NULL,
    hiring_manager character varying(255) NOT NULL,
    job_description text NOT NULL,
    experience_requirements text,
    education_requirements text,
    number_of_openings integer DEFAULT 1,
    salary_min numeric(12,2) DEFAULT 0,
    salary_max numeric(12,2) DEFAULT 0,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.jobs_master OWNER TO postgres;

--
-- Name: jobs_master_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.jobs_master_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.jobs_master_id_seq OWNER TO postgres;

--
-- Name: jobs_master_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.jobs_master_id_seq OWNED BY public.jobs_master.id;


--
-- Name: links; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.links (
    id integer NOT NULL,
    name character varying(100) NOT NULL,
    url character varying(500) NOT NULL,
    logo_path character varying(500),
    logo_url character varying(500),
    background_color character varying(7) DEFAULT '#115948'::character varying,
    sort_order integer DEFAULT 0,
    is_active boolean DEFAULT true,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.links OWNER TO postgres;

--
-- Name: links_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.links_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.links_id_seq OWNER TO postgres;

--
-- Name: links_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.links_id_seq OWNED BY public.links.id;


--
-- Name: location_master; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.location_master (
    id integer NOT NULL,
    unique_location character varying(255) NOT NULL,
    total_assets integer DEFAULT 0,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.location_master OWNER TO postgres;

--
-- Name: location_master_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.location_master_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.location_master_id_seq OWNER TO postgres;

--
-- Name: location_master_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.location_master_id_seq OWNED BY public.location_master.id;


--
-- Name: memos; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.memos (
    id integer NOT NULL,
    description text NOT NULL,
    raised_by integer,
    issued_on date DEFAULT CURRENT_DATE,
    document_path character varying(500) NOT NULL,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    raised_by_email character varying(255),
    raised_by_name character varying(255)
);


ALTER TABLE public.memos OWNER TO postgres;

--
-- Name: memos_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.memos_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.memos_id_seq OWNER TO postgres;

--
-- Name: memos_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.memos_id_seq OWNED BY public.memos.id;


--
-- Name: microsoft_groups; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.microsoft_groups (
    id bigint NOT NULL,
    azure_group_id character varying(255) NOT NULL,
    display_name character varying(255) NOT NULL,
    description text,
    members json NOT NULL,
    member_count integer DEFAULT 0 NOT NULL,
    last_synced_at timestamp(0) without time zone NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.microsoft_groups OWNER TO postgres;

--
-- Name: microsoft_groups_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.microsoft_groups_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.microsoft_groups_id_seq OWNER TO postgres;

--
-- Name: microsoft_groups_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.microsoft_groups_id_seq OWNED BY public.microsoft_groups.id;


--
-- Name: migrations; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.migrations (
    id integer NOT NULL,
    migration character varying(255) NOT NULL,
    batch integer NOT NULL
);


ALTER TABLE public.migrations OWNER TO postgres;

--
-- Name: migrations_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.migrations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.migrations_id_seq OWNER TO postgres;

--
-- Name: migrations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.migrations_id_seq OWNED BY public.migrations.id;


--
-- Name: password_reset_tokens; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.password_reset_tokens (
    email character varying(255) NOT NULL,
    token character varying(255) NOT NULL,
    created_at timestamp(0) without time zone
);


ALTER TABLE public.password_reset_tokens OWNER TO postgres;

--
-- Name: role_tag; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.role_tag (
    role text NOT NULL,
    tag text NOT NULL
);


ALTER TABLE public.role_tag OWNER TO postgres;

--
-- Name: sessions; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.sessions (
    id character varying(255) NOT NULL,
    user_id bigint,
    ip_address character varying(45),
    user_agent text,
    payload text NOT NULL,
    last_activity integer NOT NULL
);


ALTER TABLE public.sessions OWNER TO postgres;

--
-- Name: users; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.users (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    email character varying(255) NOT NULL,
    email_verified_at timestamp(0) without time zone,
    password character varying(255) NOT NULL,
    remember_token character varying(100),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    group_id integer
);


ALTER TABLE public.users OWNER TO postgres;

--
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.users_id_seq OWNER TO postgres;

--
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- Name: allocated_asset_master id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.allocated_asset_master ALTER COLUMN id SET DEFAULT nextval('public.allocated_asset_master_id_seq'::regclass);


--
-- Name: approvals id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.approvals ALTER COLUMN id SET DEFAULT nextval('public.approvals_id_seq'::regclass);


--
-- Name: asset_master id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.asset_master ALTER COLUMN id SET DEFAULT nextval('public.asset_master_id_seq'::regclass);


--
-- Name: asset_type_master id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.asset_type_master ALTER COLUMN id SET DEFAULT nextval('public.asset_type_master_id_seq'::regclass);


--
-- Name: candidate_skill_master id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.candidate_skill_master ALTER COLUMN id SET DEFAULT nextval('public.candidate_skill_master_id_seq'::regclass);


--
-- Name: candidate_source_master id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.candidate_source_master ALTER COLUMN id SET DEFAULT nextval('public.candidate_source_master_id_seq'::regclass);


--
-- Name: candidates_master id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.candidates_master ALTER COLUMN id SET DEFAULT nextval('public.candidates_master_id_seq'::regclass);


--
-- Name: failed_jobs id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.failed_jobs ALTER COLUMN id SET DEFAULT nextval('public.failed_jobs_id_seq'::regclass);


--
-- Name: group_personalized_links id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.group_personalized_links ALTER COLUMN id SET DEFAULT nextval('public.group_personalized_links_id_seq'::regclass);


--
-- Name: groups id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.groups ALTER COLUMN id SET DEFAULT nextval('public.groups_id_seq'::regclass);


--
-- Name: jobs id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.jobs ALTER COLUMN id SET DEFAULT nextval('public.jobs_id_seq'::regclass);


--
-- Name: jobs_master id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.jobs_master ALTER COLUMN id SET DEFAULT nextval('public.jobs_master_id_seq'::regclass);


--
-- Name: links id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.links ALTER COLUMN id SET DEFAULT nextval('public.links_id_seq'::regclass);


--
-- Name: location_master id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.location_master ALTER COLUMN id SET DEFAULT nextval('public.location_master_id_seq'::regclass);


--
-- Name: memos id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.memos ALTER COLUMN id SET DEFAULT nextval('public.memos_id_seq'::regclass);


--
-- Name: microsoft_groups id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.microsoft_groups ALTER COLUMN id SET DEFAULT nextval('public.microsoft_groups_id_seq'::regclass);


--
-- Name: migrations id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.migrations ALTER COLUMN id SET DEFAULT nextval('public.migrations_id_seq'::regclass);


--
-- Name: users id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- Data for Name: allocated_asset_master; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.allocated_asset_master (id, asset_tag, user_email, assign_on, status, end_date, created_at, updated_at) FROM stdin;
1	FINLAP00002	john.doe@company.com	2024-01-15 09:00:00	active	\N	2025-06-11 21:12:04.468634	2025-06-11 21:12:04.468634
3	EXTLAP00001	mike.wilson@company.com	2024-03-01 11:00:00	inactive	2025-06-12 00:01:03	2025-06-11 21:12:04.468634	2025-06-12 00:01:03
4	FINLAP00002	john.doe@company.com	2024-01-15 09:00:00	active	\N	2025-06-13 19:48:26.53764	2025-06-13 19:48:26.53764
6	EXTLAP00001	mike.wilson@company.com	2024-03-01 11:00:00	inactive	2025-06-13 22:06:28	2025-06-13 19:48:26.53764	2025-06-13 22:06:28
2	EXTMOB00001	jane.smith@company.com	2024-02-01 10:30:00	inactive	2025-06-13 22:07:18	2025-06-11 21:12:04.468634	2025-06-13 22:07:18
5	EXTMOB00001	jane.smith@company.com	2024-02-01 10:30:00	inactive	2025-06-13 22:07:18	2025-06-13 19:48:26.53764	2025-06-13 22:07:18
\.


--
-- Data for Name: approvals; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.approvals (id, memo_id, status, comment, approved_at, created_at, updated_at, approved_by_email, declined_by_email, declined_by_name, required_group_name, group_priority, approved_by_name) FROM stdin;
1	1	approved	\N	2025-06-03 11:48:03	2025-06-03 11:46:36	2025-06-03 17:18:03.161771	sparsh.gupta@finfinity.co.in	\N	\N	IT team	1	Sparsh Gupta
2	2	declined	testing	\N	2025-06-03 11:48:34	2025-06-03 17:19:06.936625	\N	sparsh.gupta@finfinity.co.in	Sparsh Gupta	IT team	1	\N
3	3	declined	testing mail	\N	2025-06-03 11:57:29	2025-06-03 17:28:35.600074	\N	sparsh.gupta@finfinity.co.in	Sparsh Gupta	IT team	1	\N
4	4	declined	testing the mailing service	\N	2025-06-03 12:13:14	2025-06-03 17:43:37.252044	\N	sparsh.gupta@finfinity.co.in	Sparsh Gupta	IT team	1	\N
6	6	approved	\N	2025-06-03 12:21:52	2025-06-03 12:21:32	2025-06-03 17:51:52.498515	sparsh.gupta@finfinity.co.in	\N	\N	IT team	1	Sparsh Gupta
5	5	declined	testing the mailing	\N	2025-06-03 12:21:31	2025-06-03 17:52:21.838599	\N	sparsh.gupta@finfinity.co.in	Sparsh Gupta	IT team	1	\N
7	7	declined	hello world	\N	2025-06-05 13:25:17	2025-06-05 18:55:52.53842	\N	sparsh.gupta@finfinity.co.in	Sparsh Gupta	IT team	1	\N
9	9	declined	tst	\N	2025-06-12 00:02:32	2025-06-12 05:32:58.304603	\N	sparsh.gupta@finfinity.co.in	Sparsh Gupta	IT team	1	\N
8	8	declined	test	\N	2025-06-11 04:51:42	2025-06-12 05:33:27.896973	\N	sparsh.gupta@finfinity.co.in	Sparsh Gupta	IT team	1	\N
10	10	declined	tst	\N	2025-06-13 21:35:26	2025-06-14 03:06:10.176478	\N	sparsh.gupta@finfinity.co.in	Sparsh Gupta	IT team	1	\N
\.


--
-- Data for Name: asset_master; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.asset_master (id, type, ownership, warranty, warranty_start, warranty_end, serial_number, tag, model, location, status, created_at, updated_at) FROM stdin;
9	Laptop	SGPL	Under Warranty	2019-06-02	2025-06-02	ppls98kjj	FINLAP00003	g351	IT Department	decommissioned	2025-06-11 23:59:54	2025-06-12 00:01:16
2	Laptop	SGPL	Under Warranty	2024-02-01	2026-02-01	HP987654321	FINLAP00002	HP EliteBook 840	Office Floor 2	active	2025-06-11 21:12:04.463273	2025-06-11 21:12:04.463273
8	Desktop	SGPL	Under Warranty	2024-04-01	2026-04-01	DP444555666	FINDSK00002	HP EliteDesk 800	IT Department	decommissioned	2025-06-11 21:12:04.463273	2025-06-13 22:05:04
6	Keyboard	SGPL	Under Warranty	2024-03-01	2025-03-01	LK111333555	FINKEY00001	Logitech K380	Office Floor 2	decommissioned	2025-06-11 21:12:04.463273	2025-06-13 22:05:11
5	Mouse	SGPL	Out of Warranty	\N	\N	LM888999000	FINMOU00001	Logitech MX Master 3	Office Floor 1	decommissioned	2025-06-11 21:12:04.463273	2025-06-13 22:05:11
3	Desktop	SGPL	Under Warranty	2024-01-15	2026-01-15	DP111222333	FINDSK00001	Dell OptiPlex 7090	Office Floor 1	decommissioned	2025-06-11 21:12:04.463273	2025-06-13 22:05:11
1	Laptop	SGPL	Under Warranty	2024-01-01	2026-01-01	DL123456789	FINLAP00001	Dell Latitude 5520	Office Floor 1	decommissioned	2025-06-11 21:12:04.463273	2025-06-13 22:05:11
18	Laptop	SGPL	Under Warranty	2004-03-02	2006-05-04	kkjhsttr	FINLAP00004	gt360	IT Department	decommissioned	2025-06-13 22:06:08	2025-06-13 22:06:21
7	Laptop	BYOD	NA	\N	\N	MB777888999	EXTLAP00001	MacBook Pro M2	Remote Work	decommissioned	2025-06-11 21:12:04.463273	2025-06-13 22:06:42
4	Mobile	Rental	NA	\N	\N	IP555666777	EXTMOB00001	iPhone 14 Pro	Remote Work	decommissioned	2025-06-11 21:12:04.463273	2025-06-13 22:07:26
\.


--
-- Data for Name: asset_type_master; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.asset_type_master (id, type, keyword, created_at, updated_at) FROM stdin;
1	Laptop	LAP	2025-06-11 21:12:04.435037	2025-06-11 21:12:04.435037
2	Desktop	DSK	2025-06-11 21:12:04.435037	2025-06-11 21:12:04.435037
3	Mouse	MOU	2025-06-11 21:12:04.435037	2025-06-11 21:12:04.435037
4	Keyboard	KEY	2025-06-11 21:12:04.435037	2025-06-11 21:12:04.435037
5	Mobile	MOB	2025-06-11 21:12:04.435037	2025-06-11 21:12:04.435037
\.


--
-- Data for Name: cache; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cache (key, value, expiration) FROM stdin;
\.


--
-- Data for Name: cache_locks; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cache_locks (key, owner, expiration) FROM stdin;
\.


--
-- Data for Name: candidate_skill_master; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.candidate_skill_master (id, skill_name, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: candidate_skills; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.candidate_skills (candidate_id, skill_id, created_at) FROM stdin;
\.


--
-- Data for Name: candidate_source_master; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.candidate_source_master (id, source_name, created_at, updated_at) FROM stdin;
1	Referral	2025-06-13 22:16:36.335441	2025-06-13 22:16:36.335441
2	LinkedIn	2025-06-13 22:16:36.335441	2025-06-13 22:16:36.335441
3	Job Portal	2025-06-13 22:16:36.335441	2025-06-13 22:16:36.335441
4	Direct Application	2025-06-13 22:16:36.335441	2025-06-13 22:16:36.335441
5	Campus Recruitment	2025-06-13 22:16:36.335441	2025-06-13 22:16:36.335441
6	Agency	2025-06-13 22:16:36.335441	2025-06-13 22:16:36.335441
7	Other	2025-06-13 22:16:36.335441	2025-06-13 22:16:36.335441
\.


--
-- Data for Name: candidates_master; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.candidates_master (id, name, email, phone, source_id, resume_path, current_status, notes, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: doc_tag; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.doc_tag (document_id, tag) FROM stdin;
1	all
2	all
3	all
4	all
5	all
6	all
7	all
\.


--
-- Data for Name: failed_jobs; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.failed_jobs (id, uuid, connection, queue, payload, exception, failed_at) FROM stdin;
\.


--
-- Data for Name: group_personalized_links; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.group_personalized_links (id, microsoft_group_name, link_name, link_url, link_logo, background_color, sort_order, replaces_link, is_active, created_at, updated_at) FROM stdin;
11	IT team	HR Admin	/hr-admin-tools	\N	#115948	1	outlook	t	2025-06-10 18:26:48.176365	2025-06-10 18:26:48.176365
12	Synergenius Growth Pvt. Ltd.	IT Tools	/it-admin-tools	\N	#115948	1	outlook	t	2025-06-10 18:26:48.177269	2025-06-10 18:26:48.177269
\.


--
-- Data for Name: groups; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.groups (id, name, priority, created_at, updated_at) FROM stdin;
1	Executives	1	2025-06-03 04:50:04.462345	2025-06-03 04:50:04.462345
2	Management	2	2025-06-03 04:50:04.462345	2025-06-03 04:50:04.462345
3	Senior Staff	3	2025-06-03 04:50:04.462345	2025-06-03 04:50:04.462345
4	Staff	4	2025-06-03 04:50:04.462345	2025-06-03 04:50:04.462345
5	Interns	5	2025-06-03 04:50:04.462345	2025-06-03 04:50:04.462345
6	IT Administrators	1	2025-06-03 04:50:04.462345	2025-06-03 04:50:04.462345
7	HR Team	2	2025-06-03 04:50:04.462345	2025-06-03 04:50:04.462345
8	Finance Team	3	2025-06-03 04:50:04.462345	2025-06-03 04:50:04.462345
9	Engineering Team	4	2025-06-03 04:50:04.462345	2025-06-03 04:50:04.462345
10	Sales Team	4	2025-06-03 04:50:04.462345	2025-06-03 04:50:04.462345
21	IT Admin	1	2025-06-09 21:15:50.587668	2025-06-09 21:15:50.587668
22	HR Admin	2	2025-06-09 21:15:50.587668	2025-06-09 21:15:50.587668
31	IT team	1	2025-06-10 18:26:48.17236	2025-06-10 18:26:48.17236
32	FinFinity IT	1	2025-06-10 18:26:48.17236	2025-06-10 18:26:48.17236
33	SGPLDPT_IT	1	2025-06-10 18:26:48.17236	2025-06-10 18:26:48.17236
34	SGPL ALL USERS	5	2025-06-10 18:26:48.17236	2025-06-10 18:26:48.17236
\.


--
-- Data for Name: job_batches; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.job_batches (id, name, total_jobs, pending_jobs, failed_jobs, failed_job_ids, options, cancelled_at, created_at, finished_at) FROM stdin;
\.


--
-- Data for Name: jobs; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.jobs (id, queue, payload, attempts, reserved_at, available_at, created_at) FROM stdin;
\.


--
-- Data for Name: jobs_master; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.jobs_master (id, job_title, department, location, hiring_manager, job_description, experience_requirements, education_requirements, number_of_openings, salary_min, salary_max, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: links; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.links (id, name, url, logo_path, logo_url, background_color, sort_order, is_active, created_at, updated_at) FROM stdin;
1	Keka	https://keka.com	\N	/assets/keka.png	#115948	1	t	2025-06-06 19:02:33.164986	2025-06-06 19:02:33.164986
2	Zoho	https://zoho.com	\N	/assets/zoho.png	#115948	2	t	2025-06-06 19:02:33.164986	2025-06-06 19:02:33.164986
3	test	.	\N	\N	#115948	0	t	2025-06-14 00:34:55.551578	2025-06-14 00:34:55.551578
4	test	.	\N	\N	#115948	0	t	2025-06-14 00:34:55.551578	2025-06-14 00:34:55.551578
5	test	.	\N	\N	#115948	0	t	2025-06-14 00:34:55.551578	2025-06-14 00:34:55.551578
6	test	.	\N	\N	#115948	0	t	2025-06-14 00:34:55.551578	2025-06-14 00:34:55.551578
7	test	.	\N	\N	#115948	0	t	2025-06-14 00:34:55.551578	2025-06-14 00:34:55.551578
\.


--
-- Data for Name: location_master; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.location_master (id, unique_location, total_assets, created_at, updated_at) FROM stdin;
1	Office Floor 1	0	2025-06-11 21:12:04.442697	2025-06-11 21:12:04.442697
2	Office Floor 2	0	2025-06-11 21:12:04.442697	2025-06-11 21:12:04.442697
3	Office Floor 3	0	2025-06-11 21:12:04.442697	2025-06-11 21:12:04.442697
4	Remote Work	0	2025-06-11 21:12:04.442697	2025-06-11 21:12:04.442697
5	Conference Room A	0	2025-06-11 21:12:04.442697	2025-06-11 21:12:04.442697
6	Conference Room B	0	2025-06-11 21:12:04.442697	2025-06-11 21:12:04.442697
7	Storage Room	0	2025-06-11 21:12:04.442697	2025-06-11 21:12:04.442697
8	IT Department	0	2025-06-11 21:12:04.442697	2025-06-11 21:12:04.442697
\.


--
-- Data for Name: memos; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.memos (id, description, raised_by, issued_on, document_path, created_at, updated_at, raised_by_email, raised_by_name) FROM stdin;
1	test	\N	2025-06-03	memos/Mj9bdsh2sXuuapGRi9w5E4vWACxIWcMf5ZC1jSw8.pdf	2025-06-03 11:46:35	2025-06-03 11:46:35	Sashwat.prj@finfinity.co.in	Sashwat Panda
2	test	\N	2025-06-03	memos/z3FsUyVvl19tU59lnzDhz0VCWXE7z3UzSySTI87Q.pdf	2025-06-03 11:48:34	2025-06-03 11:48:34	Sashwat.prj@finfinity.co.in	Sashwat Panda
3	pl approve	\N	2025-06-03	memos/c7O9QTSR61K2tRRWwgBvp3RCg7KwgzSRkuILRbWb.pdf	2025-06-03 11:57:29	2025-06-03 11:57:29	Sashwat.prj@finfinity.co.in	Sashwat Panda
4	test	\N	2025-06-03	memos/KE7iHEGGB3xYM5xK255l7KKvdYgkc12m4R2tuO2V.pdf	2025-06-03 12:13:14	2025-06-03 12:13:14	Sashwat.prj@finfinity.co.in	Sashwat Panda
5	test	\N	2025-06-03	memos/6j7Y8lTDRTLaUGgnL65wDp8Rfxr4TwtQeecc9gZQ.pdf	2025-06-03 12:21:31	2025-06-03 12:21:31	Sashwat.prj@finfinity.co.in	Sashwat Panda
6	test	\N	2025-06-03	memos/eW7IiH34Sa2qIJLZzePdSXfgrPvklwicZdGCX8sk.pdf	2025-06-03 12:21:31	2025-06-03 12:21:31	Sashwat.prj@finfinity.co.in	Sashwat Panda
7	test	\N	2025-06-05	memos/BVRrEjgE3VsozUYIu50Fuc7RYSJQGd95THHL2559.pdf	2025-06-05 13:25:17	2025-06-05 13:25:17	Sashwat.prj@finfinity.co.in	Sashwat Panda
8	test	\N	2025-06-11	memos/0wdY5DS8rOLwvWK6P7jOsCiQhe1tlh6tcAShZVpp.pdf	2025-06-11 04:51:42	2025-06-11 04:51:42	Sashwat.prj@finfinity.co.in	Sashwat Panda
9	tst	\N	2025-06-12	memos/rTKttxn22NjIXYpDGsp253FY5eZlg87VbqjcAppT.pdf	2025-06-12 00:02:32	2025-06-12 00:02:32	Sashwat.prj@finfinity.co.in	Sashwat Panda
10	tst	\N	2025-06-13	memos/PmDBQhFcKaGyxUl7yxP3ZHo4tHD9MyNbqATlpGqO.pdf	2025-06-13 21:35:26	2025-06-13 21:35:26	Sashwat.prj@finfinity.co.in	Sashwat Panda
\.


--
-- Data for Name: microsoft_groups; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.microsoft_groups (id, azure_group_id, display_name, description, members, member_count, last_synced_at, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: migrations; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.migrations (id, migration, batch) FROM stdin;
1	0001_01_01_000000_create_users_table	1
7	0001_01_01_000001_create_cache_table	2
8	0001_01_01_000002_create_jobs_table	2
9	2025_01_16_create_microsoft_groups_table	2
10	2025_06_02_234135_add_email_fields_to_memo_tables	2
11	2025_06_02_235325_add_approval_tracking_fields	2
12	2025_06_02_235800_redesign_approvals_for_group_hierarchy	2
13	2025_01_16_100000_create_links_table	3
\.


--
-- Data for Name: password_reset_tokens; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.password_reset_tokens (email, token, created_at) FROM stdin;
\.


--
-- Data for Name: role_tag; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.role_tag (role, tag) FROM stdin;
Intern	all
Head Digital Product	all
IT team	all
\.


--
-- Data for Name: sessions; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.sessions (id, user_id, ip_address, user_agent, payload, last_activity) FROM stdin;
\.


--
-- Data for Name: users; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.users (id, name, email, email_verified_at, password, remember_token, created_at, updated_at, group_id) FROM stdin;
\.


--
-- Name: allocated_asset_master_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.allocated_asset_master_id_seq', 6, true);


--
-- Name: approvals_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.approvals_id_seq', 10, true);


--
-- Name: asset_master_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.asset_master_id_seq', 18, true);


--
-- Name: asset_type_master_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.asset_type_master_id_seq', 10, true);


--
-- Name: candidate_skill_master_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.candidate_skill_master_id_seq', 1, false);


--
-- Name: candidate_source_master_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.candidate_source_master_id_seq', 7, true);


--
-- Name: candidates_master_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.candidates_master_id_seq', 1, false);


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.failed_jobs_id_seq', 1, false);


--
-- Name: group_personalized_links_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.group_personalized_links_id_seq', 13, true);


--
-- Name: groups_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.groups_id_seq', 34, true);


--
-- Name: jobs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.jobs_id_seq', 1, false);


--
-- Name: jobs_master_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.jobs_master_id_seq', 1, false);


--
-- Name: links_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.links_id_seq', 2, true);


--
-- Name: location_master_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.location_master_id_seq', 16, true);


--
-- Name: memos_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.memos_id_seq', 10, true);


--
-- Name: microsoft_groups_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.microsoft_groups_id_seq', 1, false);


--
-- Name: migrations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.migrations_id_seq', 13, true);


--
-- Name: users_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.users_id_seq', 2, true);


--
-- Name: allocated_asset_master allocated_asset_master_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.allocated_asset_master
    ADD CONSTRAINT allocated_asset_master_pkey PRIMARY KEY (id);


--
-- Name: approvals approvals_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.approvals
    ADD CONSTRAINT approvals_pkey PRIMARY KEY (id);


--
-- Name: asset_master asset_master_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.asset_master
    ADD CONSTRAINT asset_master_pkey PRIMARY KEY (id);


--
-- Name: asset_master asset_master_serial_number_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.asset_master
    ADD CONSTRAINT asset_master_serial_number_key UNIQUE (serial_number);


--
-- Name: asset_master asset_master_tag_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.asset_master
    ADD CONSTRAINT asset_master_tag_key UNIQUE (tag);


--
-- Name: asset_type_master asset_type_master_keyword_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.asset_type_master
    ADD CONSTRAINT asset_type_master_keyword_key UNIQUE (keyword);


--
-- Name: asset_type_master asset_type_master_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.asset_type_master
    ADD CONSTRAINT asset_type_master_pkey PRIMARY KEY (id);


--
-- Name: asset_type_master asset_type_master_type_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.asset_type_master
    ADD CONSTRAINT asset_type_master_type_key UNIQUE (type);


--
-- Name: cache_locks cache_locks_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cache_locks
    ADD CONSTRAINT cache_locks_pkey PRIMARY KEY (key);


--
-- Name: cache cache_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cache
    ADD CONSTRAINT cache_pkey PRIMARY KEY (key);


--
-- Name: candidate_skill_master candidate_skill_master_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.candidate_skill_master
    ADD CONSTRAINT candidate_skill_master_pkey PRIMARY KEY (id);


--
-- Name: candidate_skill_master candidate_skill_master_skill_name_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.candidate_skill_master
    ADD CONSTRAINT candidate_skill_master_skill_name_key UNIQUE (skill_name);


--
-- Name: candidate_skills candidate_skills_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.candidate_skills
    ADD CONSTRAINT candidate_skills_pkey PRIMARY KEY (candidate_id, skill_id);


--
-- Name: candidate_source_master candidate_source_master_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.candidate_source_master
    ADD CONSTRAINT candidate_source_master_pkey PRIMARY KEY (id);


--
-- Name: candidate_source_master candidate_source_master_source_name_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.candidate_source_master
    ADD CONSTRAINT candidate_source_master_source_name_key UNIQUE (source_name);


--
-- Name: candidates_master candidates_master_email_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.candidates_master
    ADD CONSTRAINT candidates_master_email_key UNIQUE (email);


--
-- Name: candidates_master candidates_master_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.candidates_master
    ADD CONSTRAINT candidates_master_pkey PRIMARY KEY (id);


--
-- Name: doc_tag doc_tag_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.doc_tag
    ADD CONSTRAINT doc_tag_pkey PRIMARY KEY (document_id, tag);


--
-- Name: failed_jobs failed_jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_uuid_unique UNIQUE (uuid);


--
-- Name: group_personalized_links group_personalized_links_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.group_personalized_links
    ADD CONSTRAINT group_personalized_links_pkey PRIMARY KEY (id);


--
-- Name: groups groups_name_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.groups
    ADD CONSTRAINT groups_name_key UNIQUE (name);


--
-- Name: groups groups_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.groups
    ADD CONSTRAINT groups_pkey PRIMARY KEY (id);


--
-- Name: job_batches job_batches_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.job_batches
    ADD CONSTRAINT job_batches_pkey PRIMARY KEY (id);


--
-- Name: jobs_master jobs_master_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.jobs_master
    ADD CONSTRAINT jobs_master_pkey PRIMARY KEY (id);


--
-- Name: jobs jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.jobs
    ADD CONSTRAINT jobs_pkey PRIMARY KEY (id);


--
-- Name: links links_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.links
    ADD CONSTRAINT links_pkey PRIMARY KEY (id);


--
-- Name: location_master location_master_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.location_master
    ADD CONSTRAINT location_master_pkey PRIMARY KEY (id);


--
-- Name: location_master location_master_unique_location_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.location_master
    ADD CONSTRAINT location_master_unique_location_key UNIQUE (unique_location);


--
-- Name: memos memos_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.memos
    ADD CONSTRAINT memos_pkey PRIMARY KEY (id);


--
-- Name: microsoft_groups microsoft_groups_azure_group_id_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.microsoft_groups
    ADD CONSTRAINT microsoft_groups_azure_group_id_unique UNIQUE (azure_group_id);


--
-- Name: microsoft_groups microsoft_groups_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.microsoft_groups
    ADD CONSTRAINT microsoft_groups_pkey PRIMARY KEY (id);


--
-- Name: migrations migrations_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.migrations
    ADD CONSTRAINT migrations_pkey PRIMARY KEY (id);


--
-- Name: password_reset_tokens password_reset_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.password_reset_tokens
    ADD CONSTRAINT password_reset_tokens_pkey PRIMARY KEY (email);


--
-- Name: role_tag role_tag_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.role_tag
    ADD CONSTRAINT role_tag_pkey PRIMARY KEY (role, tag);


--
-- Name: sessions sessions_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sessions
    ADD CONSTRAINT sessions_pkey PRIMARY KEY (id);


--
-- Name: group_personalized_links unique_group_replaces; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.group_personalized_links
    ADD CONSTRAINT unique_group_replaces UNIQUE (microsoft_group_name, replaces_link);


--
-- Name: users users_email_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_email_unique UNIQUE (email);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: idx_approvals_memo_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_approvals_memo_id ON public.approvals USING btree (memo_id);


--
-- Name: idx_approvals_status; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_approvals_status ON public.approvals USING btree (status);


--
-- Name: idx_doc_tag_docid; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_doc_tag_docid ON public.doc_tag USING btree (document_id);


--
-- Name: idx_doc_tag_tag; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_doc_tag_tag ON public.doc_tag USING btree (tag);


--
-- Name: idx_links_active_sort; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_links_active_sort ON public.links USING btree (is_active, sort_order);


--
-- Name: idx_memos_raised_by; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_memos_raised_by ON public.memos USING btree (raised_by);


--
-- Name: idx_role_tag_tag; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_role_tag_tag ON public.role_tag USING btree (tag);


--
-- Name: idx_users_group_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_users_group_id ON public.users USING btree (group_id);


--
-- Name: jobs_queue_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX jobs_queue_index ON public.jobs USING btree (queue);


--
-- Name: microsoft_groups_display_name_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX microsoft_groups_display_name_index ON public.microsoft_groups USING btree (display_name);


--
-- Name: microsoft_groups_last_synced_at_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX microsoft_groups_last_synced_at_index ON public.microsoft_groups USING btree (last_synced_at);


--
-- Name: sessions_last_activity_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX sessions_last_activity_index ON public.sessions USING btree (last_activity);


--
-- Name: sessions_user_id_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX sessions_user_id_index ON public.sessions USING btree (user_id);


--
-- Name: approvals update_approvals_updated_at; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER update_approvals_updated_at BEFORE UPDATE ON public.approvals FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: candidate_skill_master update_candidate_skill_master_updated_at; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER update_candidate_skill_master_updated_at BEFORE UPDATE ON public.candidate_skill_master FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: candidate_source_master update_candidate_source_master_updated_at; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER update_candidate_source_master_updated_at BEFORE UPDATE ON public.candidate_source_master FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: candidates_master update_candidates_master_updated_at; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER update_candidates_master_updated_at BEFORE UPDATE ON public.candidates_master FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: groups update_groups_updated_at; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER update_groups_updated_at BEFORE UPDATE ON public.groups FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: links update_links_updated_at; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER update_links_updated_at BEFORE UPDATE ON public.links FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: memos update_memos_updated_at; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER update_memos_updated_at BEFORE UPDATE ON public.memos FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: allocated_asset_master allocated_asset_master_asset_tag_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.allocated_asset_master
    ADD CONSTRAINT allocated_asset_master_asset_tag_fkey FOREIGN KEY (asset_tag) REFERENCES public.asset_master(tag) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: approvals approvals_memo_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.approvals
    ADD CONSTRAINT approvals_memo_id_fkey FOREIGN KEY (memo_id) REFERENCES public.memos(id) ON DELETE CASCADE;


--
-- Name: asset_master asset_master_location_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.asset_master
    ADD CONSTRAINT asset_master_location_fkey FOREIGN KEY (location) REFERENCES public.location_master(unique_location) ON UPDATE CASCADE;


--
-- Name: asset_master asset_master_type_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.asset_master
    ADD CONSTRAINT asset_master_type_fkey FOREIGN KEY (type) REFERENCES public.asset_type_master(type) ON UPDATE CASCADE;


--
-- Name: candidate_skills candidate_skills_candidate_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.candidate_skills
    ADD CONSTRAINT candidate_skills_candidate_id_fkey FOREIGN KEY (candidate_id) REFERENCES public.candidates_master(id) ON DELETE CASCADE;


--
-- Name: candidate_skills candidate_skills_skill_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.candidate_skills
    ADD CONSTRAINT candidate_skills_skill_id_fkey FOREIGN KEY (skill_id) REFERENCES public.candidate_skill_master(id) ON DELETE CASCADE;


--
-- Name: candidates_master candidates_master_source_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.candidates_master
    ADD CONSTRAINT candidates_master_source_id_fkey FOREIGN KEY (source_id) REFERENCES public.candidate_source_master(id);


--
-- Name: memos memos_raised_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.memos
    ADD CONSTRAINT memos_raised_by_fkey FOREIGN KEY (raised_by) REFERENCES public.users(id);


--
-- Name: users users_group_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_group_id_fkey FOREIGN KEY (group_id) REFERENCES public.groups(id);


--
-- PostgreSQL database dump complete
--

