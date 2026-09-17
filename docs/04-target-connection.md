# Connecting targets

## 1. Black-box probe (MVP)

The target does not need code changes. The worker is just a client.

``` text
                 network
                    |
                    v
+----------+  HTTP  +------------+
| Probe    | -----> | Target API |
| worker   | <----- |            |
+----+-----+        +------------+
     |
     v
check result
```

Measure, in order, as far as the client allows:

1. DNS lookup
2. TCP connect
3. TLS handshake
4. request upload
5. time to first byte
6. response download
7. total duration

Also record: final URL after redirects, status code, byte count, error
class (`timeout`, `dns`, `tls`, `connection`, `http`, `assertion`).

If a phase cannot be measured with the HTTP library you picked, store
null for that phase. Do not fake it.

## 2. What the worker must refuse

Before connecting:

- URL scheme not http/https
- host that resolves to a blocked IP
- redirects that land on a blocked IP
- responses larger than the cap (abort, mark error)

A "successful" check against `http://127.0.0.1/` is a product bug.

## 3. OpenTelemetry (after the probe works)

Two separate uses. Do not mix them in your head.

**A. Observe Pulse.** Instrument the Laravel API, the queue, the
database, and the outbound check. This is how you debug *your* workers.

**B. Correlate with a target that already emits traces.** The probe
creates a trace context and sends it:

``` text
Probe
  |  traceparent header
  v
Target API continues the trace
  |
  v
OTel Collector
```

The check result stores `trace_id` / `span_id`. The dashboard can then
link "this check failed" to "that query took 1.7s" *if* the target is
instrumented and you have a trace backend.

That is the interesting part of the project. It is not the first part.

Zero-code instrumentation of a target is useful when you cannot change
the app. Code instrumentation is deeper. Pulse does not have to provide
either; it only has to propagate context and store the ids.

## 4. Collector shape (when you get there)

Start with one local collector. One pipeline is enough:

``` yaml
receivers:
  otlp:

processors:
  memory_limiter:
  batch:

exporters:
  # pick one backend you can actually run locally

service:
  pipelines:
    traces:
      receivers: [otlp]
      processors: [memory_limiter, batch]
      exporters: [...]
```

The collector is not optional *infrastructure* once you want traces, but
it is optional *product* until checks, incidents, and alerts work.

## 5. Do not make OTel the only signal

Use:

- HTTP probes for external availability
- traces for internal causation
- metrics later for aggregate health
- logs later for detail

Probes tell you the patient is down. Traces tell you which organ failed.
You need the first even when you never get the second.
