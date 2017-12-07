#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include <stdio.h>
#include <string.h>
#include <errno.h>
#include <sys/ioctl.h>
#include <CoreServices/CoreServices.h>

void handle_events(
    ConstFSEventStreamRef         stream_ref,
    void                          *client_callback_info,
    size_t                        num_events,
    void                          *event_paths,
    const FSEventStreamEventFlags event_flags[],
    const FSEventStreamEventId    event_ids[]
)
{
    int   i;
    char  **paths = event_paths;
    char  *path   = NULL;

    for (i = 0; i < num_events; i++) {
        path = paths[i];

		printf("%s\n", path);
		fflush(stdout);
    }
}

int main(int argc, char * argv[])
{
    CFStringRef watch_paths[argc];

    for (int i = 0; i < argc; ++i) {
        watch_paths[i] = CFStringCreateWithCString(NULL, argv[i], kCFStringEncodingUTF8);
    }

    void *callback_info = NULL; // could put stream-specific data here.

    CFArrayRef       watch_path_array = CFArrayCreate(NULL, (void *) watch_paths, argc, NULL);
    CFAbsoluteTime   latency          = .75; /* Latency in seconds */
    CFRunLoopRef     run_loop         = CFRunLoopGetMain();
    FSEventStreamRef stream;

    /* Create the stream, passing in a callback */
    stream = FSEventStreamCreate(
        NULL,
        (FSEventStreamCallback)&handle_events,
        callback_info,
        watch_path_array,
        kFSEventStreamEventIdSinceNow,
        latency,
        kFSEventStreamCreateFlagNone
    );

    FSEventStreamScheduleWithRunLoop(
        stream,
        run_loop,
        kCFRunLoopDefaultMode
    );

    FSEventStreamStart(stream);
    CFRunLoopRun();
    FSEventStreamFlushSync(stream);
    FSEventStreamStop(stream);

	return 0;
}

