import type { ElementType, ComponentPropsWithoutRef, PropsWithChildren } from "react";

interface WrapperProps<T extends ElementType> extends PropsWithChildren {
  condition?: boolean;
  element?: T;
  /**
   * Allow any additional props, but if an `element` is provided,
   * they must match that element's props
   */
  [key: string]: unknown;
}

function Wrapper<T extends ElementType = "div">(
  { condition = false, element, children, ...props }: WrapperProps<T>
) {
  if (!condition) return <>{children}</>;

  const WrapperComponent = element || "div";

  return <WrapperComponent {...(props as ComponentPropsWithoutRef<T>)}>{children}</WrapperComponent>;
}

export default Wrapper;
