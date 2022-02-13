package net.hypergo.onchat.enumerate;

import com.fasterxml.jackson.annotation.JsonFormat;

@JsonFormat(shape = JsonFormat.Shape.NUMBER)
public enum RequestStatus {
    /**
     * 等候
     */
    WAIT,
    /**
     * 同意
     */
    AGREE,
    /**
     * 拒绝
     */
    REJECT
}
